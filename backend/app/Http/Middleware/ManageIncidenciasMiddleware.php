<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ManageIncidenciasMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();
        if (!$usuario) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        $usuario->loadMissing('rol');
        $rol = strtoupper((string) $usuario->rol?->nombre);

        if (in_array($rol, ['ADMINISTRADOR', 'DOCENTE'], true)
            || $usuario->tienePermiso('REGISTRAR_INCIDENCIAS')) {
            return $next($request);
        }

        return response()->json([
            'message' => 'No tiene permisos para realizar esta acción.',
        ], 403);
    }
}
