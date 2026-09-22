<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login: devuelve token Sanctum.
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $usuario = Usuario::where('username', $request->username)
            ->where('estado', 'activo')
            ->first();

        if (! $usuario || ! Hash::check($request->password, $usuario->password_hash)) {
            throw ValidationException::withMessages([
                'username' => ['Las credenciales son incorrectas.'],
            ]);
        }

        $token = $usuario->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'usuario' => [
                'id'       => $usuario->id_usuario,
                'nombre'   => $usuario->nombre,
                'apellido' => $usuario->apellido,
                'username' => $usuario->username,
                'correo'   => $usuario->correo,
                'rol'      => $usuario->rol?->nombre,
            ],
        ]);
    }

    /**
     * Logout: revoca el token actual.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada correctamente.']);
    }

    /**
     * Devuelve los datos del usuario autenticado.
     */
    public function me(Request $request)
    {
        $usuario = $request->user()->load('rol');

        return response()->json([
            'id'       => $usuario->id_usuario,
            'nombre'   => $usuario->nombre,
            'apellido' => $usuario->apellido,
            'username' => $usuario->username,
            'correo'   => $usuario->correo,
            'rol'      => $usuario->rol?->nombre,
        ]);
    }
}
