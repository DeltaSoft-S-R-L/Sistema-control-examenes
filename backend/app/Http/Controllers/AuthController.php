<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterUserRequest;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Registro de un nuevo usuario en el sistema (Tarea #6).
     *
     * @param RegisterUserRequest $request
     * @return JsonResponse
     */
    public function register(RegisterUserRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // 1. Validar existencia del rol solicitado de forma case-insensitive
        $rolNombre = trim($validated['rol']);
        $rol = Rol::whereRaw('LOWER(nombre) = ?', [strtolower($rolNombre)])->first();

        if (!$rol) {
            return response()->json([
                'error' => "El rol '{$rolNombre}' no es válido o no existe en el sistema"
            ], 400);
        }

        // 2. Persistir el nuevo usuario con contraseña hasheada y estado ACTIVO
        $usuario = Usuario::create([
            'nombre' => trim($validated['nombre']),
            'apellido' => trim($validated['apellido']),
            'correo' => strtolower(trim($validated['correo'])),
            'username' => trim($validated['username']),
            'password_hash' => Hash::make($validated['password']),
            'id_rol' => $rol->id_rol,
            'estado' => 'ACTIVO',
        ]);

        // 3. Cargar la relación con el rol
        $usuario->load('rol');

        return response()->json([
            'message' => 'Usuario registrado exitosamente',
            'usuario' => $usuario,
        ], 201);
    }
}
