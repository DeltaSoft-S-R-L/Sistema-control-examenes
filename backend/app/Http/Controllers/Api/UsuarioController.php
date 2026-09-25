<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUsuarioRequest;
use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;

class UsuarioController extends Controller
{
    /**
     * Registrar un nuevo usuario.
     */
    public function store(StoreUsuarioRequest $request)
    {
        $data = $request->validated();

        $usuario = Usuario::create([
            'nombre' => $data['nombre'],
            'apellido' => $data['apellido'],
            'correo' => $data['correo'],
            'username' => $data['username'],
            'password_hash' => Hash::make($data['password']),
            'id_rol' => $data['id_rol'],
            'estado' => $data['estado'] ?? 'ACTIVO',
        ]);

        return response()->json([
            'message' => 'Usuario registrado correctamente.',
            'usuario' => [
                'id_usuario' => $usuario->id_usuario,
                'nombre' => $usuario->nombre,
                'apellido' => $usuario->apellido,
                'correo' => $usuario->correo,
                'username' => $usuario->username,
                'id_rol' => $usuario->id_rol,
                'estado' => $usuario->estado,
            ],
        ], 201);
    }
}