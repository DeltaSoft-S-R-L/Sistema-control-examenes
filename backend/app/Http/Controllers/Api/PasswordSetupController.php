<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use App\Utilities\PasswordHasher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PasswordSetupController extends Controller
{
    /**
     * Validar un enlace de configuración de contraseña.
     */
    public function verificar(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'token' => ['required', 'string', 'size:64'],
        ]);

        $registro = DB::table('password_setup_tokens')
            ->where('token_hash', hash('sha256', $datos['token']))
            ->where('expires_at', '>', now())
            ->first();

        if (!$registro) {
            return response()->json([
                'message' => 'El enlace es inválido o ha expirado.',
            ], 422);
        }

        $usuario = Usuario::find($registro->id_usuario);

        if (!$usuario || strtoupper($usuario->estado) !== 'ACTIVO') {
            return response()->json([
                'message' => 'La cuenta no está disponible.',
            ], 422);
        }

        return response()->json([
            'message' => 'El enlace es válido.',
            'valido' => true,
        ]);
    }

    /**
     * Establecer la contraseña mediante un token de un solo uso.
     */
    public function configurar(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'token' => ['required', 'string', 'size:64'],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/[a-zA-Z]/',
                'regex:/[0-9]/',
            ],
        ]);

        return DB::transaction(function () use ($datos) {
            $registro = DB::table('password_setup_tokens')
                ->where('token_hash', hash('sha256', $datos['token']))
                ->lockForUpdate()
                ->first();

            if (!$registro || now()->greaterThanOrEqualTo($registro->expires_at)) {
                return response()->json([
                    'message' => 'El enlace es inválido o ha expirado.',
                ], 422);
            }

            $usuario = Usuario::whereKey($registro->id_usuario)
                ->lockForUpdate()
                ->first();

            if (!$usuario || strtoupper($usuario->estado) !== 'ACTIVO') {
                return response()->json([
                    'message' => 'La cuenta no está disponible.',
                ], 422);
            }

            $usuario->password_hash = PasswordHasher::hash($datos['password']);
            $usuario->save();

            // Invalidar cualquier sesión o token de acceso anterior.
            $usuario->tokens()->delete();

            // Eliminar todos los enlaces de configuración de esta cuenta.
            DB::table('password_setup_tokens')
                ->where('id_usuario', $usuario->id_usuario)
                ->delete();

            return response()->json([
                'message' => 'Contraseña configurada correctamente. Ya puede iniciar sesión.',
            ]);
        });
    }
}
