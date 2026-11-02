<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Permite el acceso únicamente a usuarios con alguno
     * de los roles indicados en la ruta.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $usuario = $request->user();

        if (! $usuario) {
            return response()->json([
                'message' => 'No autenticado.',
            ], 401);
        }

        $usuario->loadMissing('rol');

        $nombreRol = $usuario->rol?->nombre;

        if (! $nombreRol || ! in_array($nombreRol, $roles, true)) {
            return response()->json([
                'message' => 'No tiene permisos para realizar esta acción.',
            ], 403);
        }

        return $next($request);
    }
}