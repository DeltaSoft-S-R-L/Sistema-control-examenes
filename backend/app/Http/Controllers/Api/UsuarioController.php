<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UsuarioController extends Controller
{
    public function index(Request $request)
    {
        $query = Usuario::with('rol');

        if ($request->filled('rol')) {
            $rolId = $request->input('rol');
            $query->where('id_rol', $rolId);
        }

        if ($request->filled('buscar')) {
            $buscar = $request->input('buscar');
            $query->where(function ($q) use ($buscar) {
                $q->where('nombre', 'ilike', '%' . $buscar . '%')
                  ->orWhere('apellido', 'ilike', '%' . $buscar . '%')
                  ->orWhere('username', 'ilike', '%' . $buscar . '%')
                  ->orWhere('correo', 'ilike', '%' . $buscar . '%');
            });
        }

        $usuarios = $query->orderBy('apellido')
            ->orderBy('nombre')
            ->paginate(20);

        return response()->json($usuarios);
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