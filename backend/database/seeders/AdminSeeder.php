<?php

namespace Database\Seeders;

use App\Models\Rol;
use App\Models\Permiso;
use App\Models\Usuario;
use App\Utilities\PasswordHasher;
use Illuminate\Database\Seeder;
use RuntimeException;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $username = env('ADMIN_USERNAME');
        $password = env('ADMIN_PASSWORD');

        if (!$username || !$password) {
            throw new RuntimeException(
                'Debe configurar ADMIN_USERNAME y ADMIN_PASSWORD en el archivo .env.'
            );
        }

        $rol = Rol::firstOrCreate(
            ['nombre' => 'ADMINISTRADOR'],
            ['descripcion' => 'Administrador del sistema']
        );

        Rol::firstOrCreate(
            ['nombre' => 'DOCENTE'],
            ['descripcion' => 'Docente del sistema']
        );

        Rol::firstOrCreate(
            ['nombre' => 'CONTROL_INGRESO'],
            ['descripcion' => 'Personal de control de ingreso']
        );

        $permisosAdministrador = [
            'GESTIONAR_USUARIOS',
            'GESTIONAR_ESTUDIANTES',
            'GESTIONAR_ASIGNATURAS',
            'GESTIONAR_EXAMENES',
            'GESTIONAR_AMBIENTES',
            'GESTIONAR_HABILITACIONES',
            'REGISTRAR_INGRESOS',
            'REGISTRAR_INCIDENCIAS',
        ];

        $idsPermisos = collect($permisosAdministrador)
            ->map(fn (string $nombre) => Permiso::firstOrCreate(
                ['nombre' => $nombre],
                ['descripcion' => 'Permiso de administrador: ' . strtolower(str_replace('_', ' ', $nombre))]
            )->id_permiso)
            ->all();

        $rol->permisos()->syncWithoutDetaching($idsPermisos);

        $usuario = Usuario::firstOrNew(['username' => $username]);
        $esNuevo = !$usuario->exists;
        $usuario->fill([
            'nombre' => 'Administrador',
            'apellido' => 'Sistema',
            'correo' => 'admin@control-examenes.local',
            'id_rol' => $rol->id_rol,
            'estado' => 'ACTIVO',
        ]);

        if ($esNuevo || !$usuario->password_hash) {
            $usuario->password_hash = PasswordHasher::hash($password);
        }

        $usuario->save();
    }
}
