<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUsuarioRequest;
use App\Models\Usuario;
use App\Utilities\PasswordHasher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class UsuarioController extends Controller
{
    /**
     * Registrar un nuevo usuario (USR-01, Tarea #6).
     *
     * Integra validación (#5), hashing criptográfico (#2) y
     * asignación de rol (#7).
     */
    public function store(StoreUsuarioRequest $request)
    {
        $data = $request->validated();
        $estado = 'ACTIVO';
        if (isset($data['estado'])) {
            $estado = strtoupper($data['estado']);
        } elseif (isset($data['is_active'])) {
            $estado = filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN) ? 'ACTIVO' : 'REVOCADO';
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

        // Cargar la relación de rol para incluirla en la respuesta
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
                'regex:/^[^@\s]+@umss\.edu\.bo$/i',
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
                'sometimes',
                'required_without:is_active',
                Rule::in(['ACTIVO', 'REVOCADO', 'activo', 'revocado']),
            ],
            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ]);

        if ($request->has('is_active') && !isset($datos['estado'])) {
            $datos['estado'] = $request->boolean('is_active') ? 'ACTIVO' : 'REVOCADO';
        }

        if (isset($datos['estado'])) {
            $datos['estado'] = strtoupper($datos['estado']);
        }

        $usuario->update($datos);

        // Si el estado se actualizó a REVOCADO, invalidar inmediatamente todos los tokens y sesiones (USR-03)
        if ($datos['estado'] === 'REVOCADO') {
            $usuario->tokens()->delete();
            if (Schema::hasTable('sessions')) {
                DB::table('sessions')->where('user_id', $usuario->id_usuario)->delete();
            }
        }

        $usuario->load('rol');

        return response()->json($usuario);
    }

    /**
     * Revocar acceso a un usuario (USR-03).
     *
     * Cambia el estado a 'REVOCADO', invalida todos sus tokens de Sanctum y sesiones
     * e impide la auto-revocación del administrador.
     */
    public function revocar(Request $request, string $id)
    {
        // 1. Validar autorización de rol (solo ADMINISTRADOR)
        $admin = $request->user();
        if (!$admin || strtoupper($admin->rol?->nombre ?? '') !== 'ADMINISTRADOR') {
            return response()->json([
                'message' => 'No tiene permisos para realizar esta acción.',
            ], 403);
        }

        // 2. Validar existencia del usuario objetivo
        $usuario = Usuario::with('rol')->find($id);
        if (!$usuario) {
            return response()->json([
                'message' => 'Usuario no encontrado.',
            ], 404);
        }

        // 3. Impedir auto-revocación
        if ((int) $admin->id_usuario === (int) $usuario->id_usuario) {
            return response()->json([
                'message' => 'No puede revocar su propia cuenta de administrador.',
            ], 422);
        }

        // 4. Actualizar estado a REVOCADO
        $usuario->update([
            'estado' => 'REVOCADO',
        ]);

        // 5. Invalidar todas las sesiones / tokens Sanctum del usuario revocado (USR-03)
        $usuario->tokens()->delete();
        if (Schema::hasTable('sessions')) {
            DB::table('sessions')->where('user_id', $usuario->id_usuario)->delete();
        }

        $usuario->load('rol');

        return response()->json([
            'message' => 'Cuenta revocada exitosamente',
            'data'    => $usuario,
        ], 200);
    }
}