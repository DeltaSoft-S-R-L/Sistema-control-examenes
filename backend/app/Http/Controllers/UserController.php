<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserRequest;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    /**
     * Actualización parcial o total de datos de un usuario (Tarea #9).
     *
     * @param UpdateUserRequest $request
     * @param mixed $id
     * @return JsonResponse
     */
    public function update(UpdateUserRequest $request, $id): JsonResponse
    {
        // 1. Validar que el parámetro id sea numérico entero positivo
        if (!ctype_digit((string)$id)) {
            return response()->json([
                'error' => 'Error de validación',
                'detalles' => [
                    [
                        'campo' => 'id',
                        'mensaje' => 'El parámetro id debe ser un número entero positivo'
                    ]
                ]
            ], 400);
        }

        // 2. Verificar existencia del usuario
        $usuario = Usuario::find($id);
        if (!$usuario) {
            return response()->json([
                'error' => 'Usuario no encontrado'
            ], 404);
        }

        $validated = $request->validated();

        // 3. Validar nuevo rol si se incluye en la petición
        if (isset($validated['rol'])) {
            $rolNombre = trim($validated['rol']);
            $rol = Rol::whereRaw('LOWER(nombre) = ?', [strtolower($rolNombre)])->first();

            if (!$rol) {
                return response()->json([
                    'error' => 'El rol especificado no es válido o no existe'
                ], 400);
            }

            $usuario->id_rol = $rol->id_rol;
        }

        // 4. Asignar los campos modificados (sin permitir modificar password_hash)
        if (isset($validated['nombre'])) {
            $usuario->nombre = trim($validated['nombre']);
        }
        if (isset($validated['apellido'])) {
            $usuario->apellido = trim($validated['apellido']);
        }
        if (isset($validated['correo'])) {
            $usuario->correo = strtolower(trim($validated['correo']));
        }
        if (isset($validated['username'])) {
            $usuario->username = trim($validated['username']);
        }
        if (isset($validated['estado'])) {
            $usuario->estado = $validated['estado'];
        }

        // 5. Persistir cambios y cargar relación
        $usuario->save();
        $usuario->load('rol');

        return response()->json([
            'message' => 'Usuario actualizado exitosamente',
            'usuario' => $usuario,
        ], 200);
    }
}
