<?php

namespace Database\Seeders;

use App\Models\Rol;
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

        Usuario::updateOrCreate(
            ['username' => $username],
            [
                'nombre' => 'Administrador',
                'apellido' => 'Sistema',
                'correo' => 'admin@control-examenes.local',
                'password_hash' => PasswordHasher::hash($password),
                'id_rol' => $rol->id_rol,
                'estado' => 'ACTIVO',
            ]
        );
    }
}