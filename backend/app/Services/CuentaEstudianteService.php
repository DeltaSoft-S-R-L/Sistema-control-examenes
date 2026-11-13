<?php

namespace App\Services;

use App\Models\Estudiante;
use App\Models\Rol;
use App\Models\Usuario;
use App\Utilities\PasswordHasher;
use RuntimeException;

class CuentaEstudianteService
{
    /**
     * Crea una cuenta para un estudiante existente.
     *
     * Debe ejecutarse dentro de una transacción.
     * No envía correos.
     *
     * @return Usuario|null
     */
    public function crearCuenta(Estudiante $estudiante): ?Usuario
    {
        $estudiante = Estudiante::query()
            ->lockForUpdate()
            ->findOrFail($estudiante->id_estudiante);

        // No crear cuentas duplicadas.
        if ($estudiante->id_usuario !== null) {
            return null;
        }

        // Solo los estudiantes activos reciben una cuenta.
        if (strtoupper((string) $estudiante->estado) !== 'ACTIVO') {
            return null;
        }

        $correo = trim((string) $estudiante->correo);
        $sis = trim((string) $estudiante->codigo_universitario);
        $ci = trim((string) $estudiante->ci);

        // El correo es necesario para registrar la cuenta
        // y enviar las instrucciones de acceso.
        if ($correo === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    throw new RuntimeException(
        'El estudiante debe tener un correo electrónico válido.'
    );
}

        if ($sis === '') {
            throw new RuntimeException(
                'El estudiante no tiene código universitario.'
            );
        }

        if ($ci === '') {
            throw new RuntimeException(
                'El estudiante no tiene carnet de identidad.'
            );
        }

        // Evitar utilizar credenciales de otra cuenta.
        if (
            Usuario::query()
                ->where('username', $sis)
                ->orWhere('correo', $correo)
                ->exists()
        ) {
            throw new RuntimeException(
                'El SIS o correo ya pertenece a otra cuenta.'
            );
        }

        $rol = Rol::query()
            ->where('nombre', 'ESTUDIANTE')
            ->first();

        if (!$rol) {
            throw new RuntimeException(
                'No existe el rol ESTUDIANTE.'
            );
        }

        // El CI será la contraseña inicial.
        // Se almacena únicamente su hash, nunca el CI en texto plano.
        $usuario = Usuario::create([
            'nombre' => $estudiante->nombre,
            'apellido' => $estudiante->apellido,
            'correo' => $correo,
            'username' => $sis,
            'password_hash' => PasswordHasher::hash($ci),
            'id_rol' => $rol->id_rol,
            'estado' => 'ACTIVO',
        ]);

        // Vincular la cuenta con el estudiante.
        $estudiante->id_usuario = $usuario->id_usuario;
        $estudiante->save();

        // Ya no se generan tokens de activación.
        return $usuario;
    }
}
