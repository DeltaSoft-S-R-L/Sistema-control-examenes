<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use App\Utilities\PasswordHasher;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $usuario = Usuario::where('username', $request->username)->first();

        if (! $usuario || ! PasswordHasher::verify($request->password, $usuario->password_hash)) {
            throw ValidationException::withMessages([
                'username' => ['Las credenciales son incorrectas.'],
            ]);
        }

        if (strtoupper($usuario->estado) !== 'ACTIVO') {
            throw ValidationException::withMessages([
                'username' => ['Su cuenta se encuentra inactiva o revocada. Contacte al administrador.'],
            ]);
        }

        // Impedir el inicio de sesión mientras exista un enlace
// de configuración de contraseña pendiente.
$tieneConfiguracionPendiente = DB::table('password_setup_tokens')
    ->where('id_usuario', $usuario->id_usuario)
    ->exists();

if ($tieneConfiguracionPendiente) {
    throw ValidationException::withMessages([
        'username' => [
            'Debe configurar su contraseña mediante el enlace enviado a su correo electrónico. Si el enlace expiró, contacte al administrador.'
        ],
    ]);
}
        $expiresAt = now()->addMinutes((int) config('sanctum.expiration'));
        $token = $usuario->createToken('api-token', ['*'], $expiresAt)->plainTextToken;

        return response()->json([
            'token' => $token,
            'usuario' => [
                'id'        => $usuario->id_usuario,
                'nombre'    => $usuario->nombre,
                'apellido'  => $usuario->apellido,
                'username'  => $usuario->username,
                'correo'    => $usuario->correo,
                'rol'       => $usuario->rol?->nombre,
                'estado'    => $usuario->estado,
                'is_active' => $usuario->is_active,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada correctamente.']);
    }

    public function me(Request $request)
    {
        $usuario = $request->user()->load('rol');

        return response()->json([
            'id'        => $usuario->id_usuario,
            'nombre'    => $usuario->nombre,
            'apellido'  => $usuario->apellido,
            'username'  => $usuario->username,
            'correo'    => $usuario->correo,
            'rol'       => $usuario->rol?->nombre,
            'estado'    => $usuario->estado,
            'is_active' => $usuario->is_active,
        ]);
    }
}
