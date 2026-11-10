<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    /**
     * Permite el acceso únicamente si el usuario autenticado
     * posee el permiso requerido a través de su rol.
     */
    public function handle(Request $request, Closure $next, string $permiso): Response
    {
        $usuario = $request->user();

        if (! $usuario) {
            return response()->json([
                'message' => 'No autenticado.',
            ], 401);
        }

        // El rol ADMINISTRADOR tiene acceso global; sus permisos asociados se
        // mantienen para documentar y consultar la matriz de permisos.
        $usuario->loadMissing('rol');
        if (strtoupper((string) $usuario->rol?->nombre) === 'ADMINISTRADOR') {
            return $next($request);
        }

        if (! $usuario->tienePermiso($permiso)) {
            return response()->json([
                'message' => 'No tiene permisos para realizar esta acción.',
            ], 403);
        }

        return $next($request);
    }
}
