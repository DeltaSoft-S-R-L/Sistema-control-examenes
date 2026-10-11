<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUsuarioRequest;
use App\Models\Usuario;
use App\Utilities\PasswordHasher;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UsuarioController extends Controller
{
    public function store(StoreUsuarioRequest $request)
    {
        $data = $request->validated();

        $estado = 'ACTIVO';
        if (isset($data['is_active'])) {
            $estado = filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN) ? 'ACTIVO' : 'REVOCADO';
        } elseif (isset($data['estado'])) {
            $estado = strtoupper($data['estado']);
        }

        $usuario = Usuario::create([
            'nombre'        => $data['nombre'],
            'apellido'      => $data['apellido'],
            'correo'        => $data['correo'],
            'username'      => $data['username'],
            'password_hash' => PasswordHasher::hash($data['password']),
            'id_rol'        => $data['id_rol'],
            'estado'        => $estado,
        ]);

        $usuario->load('rol');

        return response()->json([
            'message' => 'Usuario registrado correctamente.',
            'usuario' => $usuario,
        ], 201);
    }

    /**
     * Listar usuarios de forma paginada y con filtros.
     */
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
            'is_active' => ['sometimes', 'boolean'],
            'estado' => [
                'sometimes',
                Rule::in(['ACTIVO', 'REVOCADO', 'activo', 'revocado']),
            ],
        ]);

        if (array_key_exists('is_active', $datos)) {
            $datos['estado'] = filter_var($datos['is_active'], FILTER_VALIDATE_BOOLEAN) ? 'ACTIVO' : 'REVOCADO';
        } elseif (array_key_exists('estado', $datos)) {
            $datos['estado'] = strtoupper($datos['estado']);
        }

        $usuario->update($datos);

        $usuario->load('rol');

        return response()->json($usuario);
    }
}