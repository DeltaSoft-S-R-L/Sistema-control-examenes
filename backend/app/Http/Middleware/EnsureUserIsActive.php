<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if ($usuario) {
            if (strtoupper((string) $usuario->estado) !== 'ACTIVO') {
                $usuario->tokens()->delete();

                return response()->json([
                    'message' => 'Su cuenta se encuentra inactiva o revocada. Contacte al administrador.',
                ], 401);
            }
        }

        return $next($request);
    }
}
