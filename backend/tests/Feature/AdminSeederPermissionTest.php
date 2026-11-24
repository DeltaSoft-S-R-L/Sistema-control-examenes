<?php

namespace Tests\Feature;

use App\Models\Permiso;
use App\Models\Rol;
use App\Models\Usuario;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSeederPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_replaces_legacy_exam_permissions_with_admin_management_permissions(): void
    {
        $keys = ['ADMIN_USERNAME', 'ADMIN_PASSWORD'];
        $previous = [];

        foreach ($keys as $key) {
            $previous[$key] = [
                'getenv' => getenv($key),
                'env_exists' => array_key_exists($key, $_ENV),
                'env' => $_ENV[$key] ?? null,
                'server_exists' => array_key_exists($key, $_SERVER),
                'server' => $_SERVER[$key] ?? null,
            ];
        }

        putenv('ADMIN_USERNAME=admin_permission_test');
        putenv('ADMIN_PASSWORD=AdminSeederTest-2026');
        $_ENV['ADMIN_USERNAME'] = 'admin_permission_test';
        $_ENV['ADMIN_PASSWORD'] = 'AdminSeederTest-2026';
        $_SERVER['ADMIN_USERNAME'] = 'admin_permission_test';
        $_SERVER['ADMIN_PASSWORD'] = 'AdminSeederTest-2026';

        try {
            $rol = Rol::create([
                'nombre' => 'ADMINISTRADOR',
                'descripcion' => 'Administrador del sistema',
            ]);

            $permisosAntiguos = collect([
                'GESTIONAR_EXAMENES',
                'GESTIONAR_HABILITACIONES',
                'REGISTRAR_INGRESOS',
                'REGISTRAR_INCIDENCIAS',
            ])->map(fn (string $nombre) => Permiso::create([
                'nombre' => $nombre,
                'descripcion' => $nombre,
            ])->id_permiso);

            $rol->permisos()->attach($permisosAntiguos->all());

            app(AdminSeeder::class)->run();

            $this->assertSame([
                'GESTIONAR_AMBIENTES',
                'GESTIONAR_ASIGNATURAS',
                'GESTIONAR_ESTUDIANTES',
                'GESTIONAR_USUARIOS',
            ], $rol->fresh()->permisos()->orderBy('nombre')->pluck('nombre')->all());

            $this->assertDatabaseHas('usuario', [
                'username' => 'admin_permission_test',
                'id_rol' => $rol->id_rol,
                'estado' => 'ACTIVO',
            ]);
        } finally {
            foreach ($keys as $key) {
                $value = $previous[$key];
                if ($value['getenv'] === false) {
                    putenv($key);
                } else {
                    putenv($key . '=' . $value['getenv']);
                }

                if ($value['env_exists']) {
                    $_ENV[$key] = $value['env'];
                } else {
                    unset($_ENV[$key]);
                }

                if ($value['server_exists']) {
                    $_SERVER[$key] = $value['server'];
                } else {
                    unset($_SERVER[$key]);
                }
            }
        }
    }
}
