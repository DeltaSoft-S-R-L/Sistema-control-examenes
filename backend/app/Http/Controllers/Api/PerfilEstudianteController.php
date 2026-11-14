<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Estudiante;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\QrTokenService;

class PerfilEstudianteController extends Controller
{
    /**
     * Busca el perfil estudiantil vinculado al usuario autenticado.
     * Si la cuenta con rol ESTUDIANTE fue creada desde el módulo de Usuarios
     * sin vincular aún su registro en `estudiante`, lo vincula por correo/username
     * o aprovisiona el registro base para evitar bloqueos en el panel estudiantil.
     */
    private function resolverPerfilEstudiantil(Usuario $usuario): ?Estudiante
    {
        $estudiante = Estudiante::with(['carrera', 'asignaturas'])
            ->where('id_usuario', $usuario->id_usuario)
            ->first();

        if ($estudiante) {
            return $estudiante;
        }

        // Intentar vincular un estudiante existente sin cuenta asociada (por correo o código SIS)
        $estudianteSinVincular = Estudiante::whereNull('id_usuario')
            ->where(function ($query) use ($usuario) {
                $query->where('correo', $usuario->correo)
                    ->orWhere('codigo_universitario', $usuario->username);
            })
            ->first();

        if ($estudianteSinVincular) {
            $estudianteSinVincular->update([
                'id_usuario' => $usuario->id_usuario,
            ]);

            return $estudianteSinVincular->load(['carrera', 'asignaturas']);
        }

        // Aprovisionar perfil estudiantil básico si el usuario fue creado manualmente con rol ESTUDIANTE
        $codigoBase = preg_replace('/[^A-Za-z0-9]/', '', (string) $usuario->username);
        if ($codigoBase === '' || strlen($codigoBase) < 5) {
            $codigoBase = '2026' . str_pad((string) $usuario->id_usuario, 5, '0', STR_PAD_LEFT);
        }
        $codigoUniversitario = substr($codigoBase, 0, 20);
        if (Estudiante::where('codigo_universitario', $codigoUniversitario)->exists()) {
            $codigoUniversitario = '2026' . str_pad((string) $usuario->id_usuario, 5, '0', STR_PAD_LEFT);
        }

        $ciGenerado = '99' . str_pad((string) $usuario->id_usuario, 6, '0', STR_PAD_LEFT);
        while (Estudiante::where('ci', $ciGenerado)->exists()) {
            $ciGenerado = (string) random_int(10000000, 99999999);
        }

        $nuevoEstudiante = Estudiante::create([
            'ci' => $ciGenerado,
            'nombre' => $usuario->nombre,
            'apellido' => $usuario->apellido,
            'codigo_universitario' => $codigoUniversitario,
            'correo' => $usuario->correo,
            'estado' => 'ACTIVO',
            'id_usuario' => $usuario->id_usuario,
        ]);

        return $nuevoEstudiante->load(['carrera', 'asignaturas']);
    }

    public function mostrar(Request $request): JsonResponse
    {
        $usuario = $request->user();
        $usuario?->loadMissing('rol');

        // Solo los estudiantes pueden consultar este perfil.
        if (!$usuario || strtoupper((string) $usuario->rol?->nombre) !== 'ESTUDIANTE') {
            return response()->json([
                'message' => 'No tienes permiso para consultar este perfil.',
            ], 403);
        }

        $estudiante = $this->resolverPerfilEstudiantil($usuario);

        if (!$estudiante) {
            return response()->json([
                'message' => 'No se encontró un perfil estudiantil asociado a tu cuenta.',
            ], 404);
        }

        return response()->json([
            'nombre' => $estudiante->nombre,
            'apellido' => $estudiante->apellido,
            'codigo_universitario' => $estudiante->codigo_universitario,
            'correo' => $estudiante->correo,
            'carrera' => $estudiante->carrera
                ? [
                    'codigo' => $estudiante->carrera->codigo,
                    'nombre' => $estudiante->carrera->nombre,
                ]
                : null,
            'asignaturas' => $estudiante->asignaturas->map(fn ($asignatura) => [
                'codigo' => $asignatura->codigo,
                'nombre' => $asignatura->nombre,
            ])->values(),
        ]);
    }

    public function generarQr(Request $request, QrTokenService $qrTokenService): JsonResponse
    {
        $usuario = $request->user();

        $usuario?->loadMissing('rol');

        if (!$usuario || strtoupper((string) $usuario->rol?->nombre) !== 'ESTUDIANTE') {
            return response()->json([
                'message' => 'No tienes permiso para generar este código QR.',
            ], 403);
        }

        $estudiante = $this->resolverPerfilEstudiantil($usuario);

        if (!$estudiante) {
            return response()->json([
                'message' => 'No se encontró un perfil estudiantil asociado a tu cuenta.',
            ], 404);
        }

        // Generar el token versionado compatible con el validador oficial.
        $token = $qrTokenService->generar($estudiante);

        return response()->json([
            'token' => $token,
            'qr_svg' => $qrTokenService->generarSvgPorToken($token),
        ]);
    }
}
