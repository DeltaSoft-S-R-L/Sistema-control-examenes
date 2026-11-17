<?php

namespace Tests\Feature;

use App\Models\Carrera;
use App\Models\Facultad;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\Usuario;
use App\Utilities\PasswordHasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EstudianteRegistroTest extends TestCase
{
    use RefreshDatabase;

    public function test_registra_estudiante_activo_y_crea_cuenta_vinculada(): void
    {
        $rolAdmin = Rol::create(['nombre' => 'ADMINISTRADOR', 'descripcion' => 'Administrador']);
        $permiso = Permiso::create(['nombre' => 'GESTIONAR_ESTUDIANTES', 'descripcion' => 'Gestionar estudiantes']);
        $rolAdmin->permisos()->attach($permiso->id_permiso);
        Rol::firstOrCreate(
            ['nombre' => 'ESTUDIANTE'],
            ['descripcion' => 'Acceso del estudiante']
        );

        $admin = Usuario::create([
            'nombre' => 'Admin', 'apellido' => 'Prueba', 'correo' => 'admin.registro@test.bo',
            'username' => 'admin_registro', 'password_hash' => PasswordHasher::hash('Clave123!'),
            'id_rol' => $rolAdmin->id_rol, 'estado' => 'ACTIVO',
        ]);

        $facultad = Facultad::create(['codigo' => 'FCT', 'nombre' => 'Facultad de Tecnología']);
        $carrera = Carrera::create([
            'id_facultad' => $facultad->id_facultad,
            'codigo' => 'SIS',
            'nombre' => 'Ingeniería de Sistemas',
        ]);

        $respuesta = $this->actingAs($admin)->postJson('/api/estudiantes', [
            'ci' => '7654321',
            'codigo_universitario' => '20261116-TEST',
            'nombre' => 'María',
            'apellido' => 'López',
            'correo' => 'maria.registro@test.bo',
            'estado' => 'ACTIVO',
            'id_carrera' => $carrera->id_carrera,
            'asignaturas' => [],
        ]);

        $respuesta->assertCreated()
            ->assertJsonPath('cuenta_creada', true)
            ->assertJsonPath('estudiante.correo', 'maria.registro@test.bo');

        $this->assertDatabaseHas('estudiante', [
            'ci' => '7654321',
            'codigo_universitario' => '20261116-TEST',
            'id_carrera' => $carrera->id_carrera,
        ]);
        $this->assertDatabaseHas('usuario', [
            'username' => '20261116-TEST',
            'correo' => 'maria.registro@test.bo',
        ]);
        $this->assertNotNull($respuesta->json('estudiante.id_usuario'));

        $cuenta = Usuario::query()->where('username', '20261116-TEST')->firstOrFail();
        $this->assertTrue(PasswordHasher::verify('7654321', $cuenta->password_hash));
        $this->assertSame('ESTUDIANTE', $cuenta->rol->nombre);
    }
}
