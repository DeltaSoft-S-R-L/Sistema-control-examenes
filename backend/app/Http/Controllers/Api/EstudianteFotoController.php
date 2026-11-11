<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\EstudianteFotoRequest;
use App\Models\Estudiante;
use App\Models\EstudianteFoto;
use App\Services\EstudianteFotoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EstudianteFotoController extends Controller
{
    /**
     * Consulta el estado de la fotografía del estudiante autenticado.
     */
    public function mostrar(Request $request): JsonResponse
    {
        $usuario = $request->user();

        if (!$usuario || $usuario->rol?->nombre !== 'ESTUDIANTE') {
            return response()->json([
                'message' => 'No tienes permiso para consultar este recurso.',
            ], 403);
        }

        $estudiante = Estudiante::with('foto')
            ->where('id_usuario', $usuario->id_usuario)
            ->first();

        if (!$estudiante) {
            return response()->json([
                'message' => 'No se encontró un perfil estudiantil asociado a tu cuenta.',
            ], 404);
        }

        $foto = $estudiante->foto;

        return response()->json([
            'tiene_foto' => $foto !== null,
            'estado_validacion' => $foto?->estado_validacion,
            'motivo_rechazo' => $foto?->motivo_rechazo,
            'puede_subir_primera_foto' => $foto === null,
        ]);
    }

    /**
     * Registra la primera fotografía del estudiante autenticado.
     */
    public function subir(
        EstudianteFotoRequest $request,
        EstudianteFotoService $fotoService
    ): JsonResponse {
        $usuario = $request->user();

        $estudiante = Estudiante::where(
            'id_usuario',
            $usuario->id_usuario
        )->first();

        if (!$estudiante) {
            return response()->json([
                'message' => 'No se encontró tu perfil estudiantil.',
            ], 404);
        }

        $publicId = null;

        try {
            $resultado = DB::transaction(function () use (
                $estudiante,
                $request,
                $fotoService,
                &$publicId
            ) {
                // Bloquea al estudiante para evitar subidas simultáneas.
                $estudianteBloqueado = Estudiante::whereKey(
                    $estudiante->id_estudiante
                )->lockForUpdate()->firstOrFail();

                // Impide reemplazar una fotografía existente.
                if ($estudianteBloqueado->foto()->exists()) {
                    return null;
                }

                // Sube la imagen con acceso autenticado.
                $subida = $fotoService->subirFoto(
                    $request->file('foto'),
                    $estudianteBloqueado->id_estudiante
                );

                $publicId = $subida['public_id'];

                // Registra la referencia en PostgreSQL.
                return EstudianteFoto::create([
                    'id_estudiante' => $estudianteBloqueado->id_estudiante,
                    'cloudinary_public_id' => $publicId,
                    'cloudinary_type' => $subida['type'],
                    'estado_validacion' => 'pendiente',
                ]);
            });

            if ($resultado === null) {
                return response()->json([
                    'message' => 'Ya tienes una fotografía registrada. Para reemplazarla necesitas autorización administrativa.',
                ], 409);
            }

            return response()->json([
                'message' => 'Fotografía registrada correctamente.',
                'estado_validacion' => $resultado->estado_validacion,
            ], 201);

        } catch (\Throwable $e) {
            Log::error('Error al registrar fotografía estudiantil', [
                'id_estudiante' => $estudiante->id_estudiante,
                'error' => $e->getMessage(),
            ]);

            // Intenta limpiar la imagen si PostgreSQL falla
            // después de que Cloudinary confirma la subida.
            if ($publicId !== null) {
                try {
                    $fotoService->eliminarFoto($publicId);
                } catch (\Throwable $errorLimpieza) {
                    Log::critical(
                        'No se pudo limpiar una fotografía de Cloudinary',
                        [
                            'id_estudiante' => $estudiante->id_estudiante,
                            'cloudinary_public_id' => $publicId,
                            'error' => $errorLimpieza->getMessage(),
                        ]
                    );
                }
            }

            return response()->json([
                'message' => 'No fue posible registrar la fotografía.',
            ], 500);
        }
    }
}
