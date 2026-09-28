<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UsuarioController extends Controller
{
    public function index()
    {
        $usuarios = Usuario::with('rol')
            ->orderBy('apellido')
            ->orderBy('nombre')
            ->paginate(20);

        return response()->json($usuarios);
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'nombre'   => ['required', 'string', 'max:100'],
            'apellido' => ['required', 'string', 'max:100'],
            'correo'   => ['required', 'email', 'max:150', Rule::unique('usuario', 'correo')],
            'username' => ['required', 'string', 'max:50', Rule::unique('usuario', 'username')],
            'password' => ['required', 'string', 'min:6'],
            'id_rol'   => ['required', 'integer', Rule::in([1, 2, 3])],
            'estado'   => ['nullable', 'string', Rule::in(['ACTIVO', 'REVOCADO', 'activo', 'revocado'])],
        ]);

        $datos['estado'] = isset($datos['estado']) ? strtoupper($datos['estado']) : 'ACTIVO';
        $datos['password_hash'] = \App\Utilities\PasswordHasher::hash($datos['password']);
        
        unset($datos['password']);

        $usuario = Usuario::create($datos);
        $usuario->load('rol');

        return response()->json($usuario, 201);
    }

    public function update(Request $request, Usuario $usuario)
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'apellido' => ['required', 'string', 'max:100'],
            'correo' => [
                'required',
                'email',
                'max:150',
                Rule::unique('usuario', 'correo')
                    ->ignore($usuario->id_usuario, 'id_usuario'),
            ],
            'username' => [
                'required',
                'string',
                'max:50',
                Rule::unique('usuario', 'username')
                    ->ignore($usuario->id_usuario, 'id_usuario'),
            ],
            'id_rol' => [
                'required',
                'integer',
                Rule::exists('rol', 'id_rol'),
            ],
            'estado' => [
                'required',
                Rule::in(['ACTIVO', 'REVOCADO', 'activo', 'revocado']),
            ],
        ]);

        $datos['estado'] = strtoupper($datos['estado']);

        $usuario->update($datos);

        $usuario->load('rol');

        return response()->json($usuario);
    }
}