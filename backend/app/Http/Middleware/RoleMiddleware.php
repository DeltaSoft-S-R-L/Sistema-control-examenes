<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Mapeo de alias a nombres canónicos de roles en base de datos.
     */
    private const ALIAS_MAP = [
        'ADMIN'           => 'ADMINISTRADOR',
        'ADMINISTRADOR'   => 'ADMINISTRADOR',
        'CONTROL'         => 'CONTROL_INGRESO',
        'CONTROL_INGRESO' => 'CONTROL_INGRESO',
        'DOCENTE'         => 'DOCENTE',
    ];

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

        $nombreRol = strtoupper(trim((string) ($usuario->rol?->nombre ?? '')));

        if ($nombreRol === '') {
            return response()->json([
                'message' => 'No tiene permisos para realizar esta acción.',
            ], 403);
        }

        $canonicalUserRole = self::ALIAS_MAP[$nombreRol] ?? $nombreRol;

        $canonicalAllowedRoles = [];
        foreach ($roles as $r) {
            $subRoles = explode(',', $r);
            foreach ($subRoles as $subRole) {
                $subNorm = strtoupper(trim($subRole));
                if ($subNorm !== '') {
                    $canonical = self::ALIAS_MAP[$subNorm] ?? $subNorm;
                    $canonicalAllowedRoles[] = $canonical;
                    $canonicalAllowedRoles[] = $subNorm;
                }
            }
        }

        if (! in_array($canonicalUserRole, $canonicalAllowedRoles, true) && ! in_array($nombreRol, $canonicalAllowedRoles, true)) {
            return response()->json([
                'message' => 'No tiene permisos para realizar esta acción.',
            ], 403);
        }

        return $next($request);
    }
}