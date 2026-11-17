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

        // Si ya tiene una cuenta estudiantil, mantener sus credenciales
        // sincronizadas con el código y el CI registrados.
        if ($estudiante->id_usuario !== null) {
            $this->sincronizarCuenta($estudiante);
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

    /** Mantiene username=código SIS y contraseña inicial/actual=CI. */
    public function sincronizarCuenta(Estudiante $estudiante): void
    {
        if (!$estudiante->id_usuario) {
            return;
        }

        $usuario = Usuario::query()->with('rol')->lockForUpdate()
            ->find($estudiante->id_usuario);
        if (!$usuario || strtoupper((string) $usuario->rol?->nombre) !== 'ESTUDIANTE') {
            return;
        }

        $sis = trim((string) $estudiante->codigo_universitario);
        $ci = trim((string) $estudiante->ci);
        $correo = trim((string) $estudiante->correo);

        if ($sis === '' || $ci === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('El estudiante debe tener código, CI y correo válidos.');
        }

        $usuarioDuplicado = Usuario::query()
            ->where('id_usuario', '<>', $usuario->id_usuario)
            ->where(fn ($query) => $query->where('username', $sis)->orWhere('correo', $correo))
            ->exists();
        if ($usuarioDuplicado) {
            throw new RuntimeException('El código o correo ya pertenece a otra cuenta.');
        }

        $usuario->update([
            'nombre' => $estudiante->nombre,
            'apellido' => $estudiante->apellido,
            'correo' => $correo,
            'username' => $sis,
            'password_hash' => PasswordHasher::hash($ci),
            'estado' => strtoupper((string) $estudiante->estado) === 'ACTIVO' ? 'ACTIVO' : 'REVOCADO',
        ]);
    }
}
