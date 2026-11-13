<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Estudiante;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\QrTokenService;

class PerfilEstudianteController extends Controller
{
    public function mostrar(Request $request): JsonResponse
    {
        $usuario = $request->user();

        // Solo los estudiantes pueden consultar este perfil.
        if ($usuario->rol?->nombre !== 'ESTUDIANTE') {
            return response()->json([
                'message' => 'No tienes permiso para consultar este perfil.',
            ], 403);
        }

        // Buscar únicamente al estudiante vinculado
        // con el usuario autenticado.
        $estudiante = Estudiante::with([
            'carrera',
            'asignaturas',
        ])
            ->where('id_usuario', $usuario->id_usuario)
            ->first();

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

    if (!$usuario || $usuario->rol?->nombre !== 'ESTUDIANTE') {
        return response()->json([
            'message' => 'No tienes permiso para generar este código QR.',
        ], 403);
    }

    $estudiante = Estudiante::where(
        'id_usuario',
        $usuario->id_usuario
    )->first();

    if (!$estudiante) {
        return response()->json([
            'message' => 'No se encontró un perfil estudiantil asociado a tu cuenta.',
        ], 404);
    }

    // Generar un único token con el formato que espera validarToken().
    $token = QrTokenService::generarToken($estudiante);

    return response()->json([
        'token' => $token,
        'qr_svg' => $qrTokenService->generarSvgPorToken($token),
    ]);
}

}
