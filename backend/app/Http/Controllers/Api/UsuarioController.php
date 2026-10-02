<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUsuarioRequest;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

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
            'estado' => strtoupper($data['estado'] ?? 'ACTIVO'),
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
            $buscar = mb_strtolower($request->input('buscar'));
            $query->where(function ($q) use ($buscar) {
                $q->whereRaw('LOWER(nombre) LIKE ?', ['%' . $buscar . '%'])
                  ->orWhereRaw('LOWER(apellido) LIKE ?', ['%' . $buscar . '%'])
                  ->orWhereRaw('LOWER(username) LIKE ?', ['%' . $buscar . '%'])
                  ->orWhereRaw('LOWER(correo) LIKE ?', ['%' . $buscar . '%']);
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

    /**
     * Actualizar usuario.
     */
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
