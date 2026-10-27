<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
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

        if (! $nombreRol) {
            return response()->json([
                'message' => 'No tiene permisos para realizar esta acción.',
            ], 403);
        }

        $normalizedUserRole = strtoupper(trim((string) $nombreRol));
        $normalizedAllowedRoles = array_map(fn ($r) => strtoupper(trim((string) $r)), $roles);

        $authorized = in_array($normalizedUserRole, $normalizedAllowedRoles, true)
            || in_array($nombreRol, $roles, true);

        if (! $authorized) {
            $synonyms = [
                'ADMIN' => 'ADMINISTRADOR',
                'ADMINISTRADOR' => 'ADMIN',
                'CONTROL' => 'CONTROL_INGRESO',
                'CONTROL_INGRESO' => 'CONTROL',
            ];
            if (isset($synonyms[$normalizedUserRole]) && in_array($synonyms[$normalizedUserRole], $normalizedAllowedRoles, true)) {
                $authorized = true;
            }
        }

        if (! $authorized) {
            return response()->json([
                'message' => 'No tiene permisos para realizar esta acción.',
            ], 403);
        }

        return $next($request);
    }
}