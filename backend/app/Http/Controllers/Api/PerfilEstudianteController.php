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
    /** Busca sin modificar datos el registro asociado a la cuenta autenticada. */
    private function resolverPerfilEstudiantil(Usuario $usuario): ?Estudiante
    {
        return Estudiante::with([
            'carrera.facultad',
            'asignaturas',
            'habilitaciones.examen.asignatura',
        ])->where('id_usuario', $usuario->id_usuario)->first();
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
            'id_estudiante' => $estudiante->id_estudiante,
            'nombre' => $estudiante->nombre,
            'apellido' => $estudiante->apellido,
            'ci' => $estudiante->ci,
            'codigo_universitario' => $estudiante->codigo_universitario,
            'correo' => $estudiante->correo,
            'estado' => $estudiante->estado,
            'carrera' => $estudiante->carrera
                ? [
                    'codigo' => $estudiante->carrera->codigo,
                    'nombre' => $estudiante->carrera->nombre,
                    'facultad' => $estudiante->carrera->facultad?->nombre,
                ]
                : null,
            'asignaturas' => $estudiante->asignaturas->map(fn ($asignatura) => [
                'codigo' => $asignatura->codigo,
                'nombre' => $asignatura->nombre,
            ])->values(),
            'examenes' => $estudiante->habilitaciones
                ->filter(fn ($habilitacion) => $habilitacion->examen !== null)
                ->map(fn ($habilitacion) => [
                    'id_examen' => $habilitacion->examen->id_examen,
                    'nombre' => $habilitacion->examen->nombre,
                    'fecha' => $habilitacion->examen->fecha?->format('Y-m-d'),
                    'hora_inicio' => $habilitacion->examen->hora_inicio,
                    'duracion_minutos' => $habilitacion->examen->duracion_minutos,
                    'estado' => $habilitacion->examen->estado,
                    'asignatura' => $habilitacion->examen->asignatura?->nombre,
                    'estado_habilitacion' => $habilitacion->estado,
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
