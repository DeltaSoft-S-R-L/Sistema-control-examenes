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

    /**
     * Listar usuarios de forma paginada.
     */
    public function index()
    {
        $usuarios = Usuario::with('rol')
            ->orderBy('apellido')
            ->paginate(20);

        return response()->json($usuarios);
    }

    /**
     * Mostrar el detalle de un usuario.
     */
    public function show(string $id)
    {
        $usuario = Usuario::with('rol')->find($id);

        if (!$usuario) {
            return response()->json([
                'message' => 'Usuario no encontrado.',
            ], 404);
        }

        return response()->json($usuario);
    }
}