<?php

namespace Tests\Feature;

use App\Models\Estudiante;
use App\Models\Rol;
use App\Models\Usuario;
use App\Services\CuentaEstudianteService;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\Support\SQLiteSeguroTestCase;
use App\Models\Facultad;
use App\Models\Carrera;
use App\Models\Asignatura;

class PerfilEstudianteTest extends SQLiteSeguroTestCase
{
    public function test_estudiante_puede_consultar_su_perfil(): void
    {
        Rol::firstOrCreate(
            ['nombre' => 'ESTUDIANTE'],
            ['descripcion' => 'Estudiante del sistema']
        );

        $estudiante = Estudiante::create([
            'ci' => '87654321',
            'nombre' => 'Maria',
            'apellido' => 'Gomez',
            'codigo_universitario' => '202600456',
            'correo' => 'maria.perfil@example.com',
            'estado' => 'ACTIVO',
        ]);

        $usuario = DB::transaction(function () use ($estudiante) {
            return app(CuentaEstudianteService::class)
                ->crearCuenta($estudiante);
        });

        $this->assertInstanceOf(Usuario::class, $usuario);

        Sanctum::actingAs($usuario);

        $response = $this->getJson('/api/estudiante/perfil');

        $response->assertOk()
            ->assertJsonPath('nombre', 'Maria')
            ->assertJsonPath('apellido', 'Gomez')
            ->assertJsonPath('codigo_universitario', '202600456')
            ->assertJsonPath('correo', 'maria.perfil@example.com')
            ->assertJsonPath('carrera', null)
            ->assertJsonCount(0, 'asignaturas')
            ->assertJsonMissingPath('ci');
    }


public function test_estudiante_puede_consultar_carrera_y_asignaturas(): void
{
    Rol::firstOrCreate(
        ['nombre' => 'ESTUDIANTE'],
        ['descripcion' => 'Estudiante del sistema']
    );

    $facultad = Facultad::create([
        'codigo' => 'FING',
        'nombre' => 'Facultad de Ingeniería',
    ]);

    $carrera = Carrera::create([
        'id_facultad' => $facultad->id_facultad,
        'codigo' => 'SIS',
        'nombre' => 'Ingeniería de Sistemas',
    ]);

    $asignatura1 = Asignatura::create([
        'codigo' => 'MAT101',
        'nombre' => 'Matemática I',
        'descripcion' => 'Asignatura de prueba',
    ]);

    $asignatura2 = Asignatura::create([
        'codigo' => 'INF101',
        'nombre' => 'Programación I',
        'descripcion' => 'Asignatura de prueba',
    ]);

    $asignatura1->carreras()->attach($carrera->id_carrera);
    $asignatura2->carreras()->attach($carrera->id_carrera);

    $estudiante = Estudiante::create([
        'ci' => '12345678',
        'nombre' => 'Carlos',
        'apellido' => 'Lopez',
        'codigo_universitario' => '202600123',
        'correo' => 'carlos.perfil@example.com',
        'estado' => 'ACTIVO',
        'id_carrera' => $carrera->id_carrera,
    ]);

    $estudiante->asignaturas()->attach([
        $asignatura1->id_asignatura,
        $asignatura2->id_asignatura,
    ]);

    $usuario = DB::transaction(function () use ($estudiante) {
        return app(CuentaEstudianteService::class)
            ->crearCuenta($estudiante);
    });

    Sanctum::actingAs($usuario);

    $response = $this->getJson('/api/estudiante/perfil');

    $response->assertOk()
        ->assertJsonPath('nombre', 'Carlos')
        ->assertJsonPath('codigo_universitario', '202600123')
        ->assertJsonPath('carrera.codigo', 'SIS')
        ->assertJsonPath('carrera.nombre', 'Ingeniería de Sistemas')
        ->assertJsonCount(2, 'asignaturas')
        ->assertJsonFragment([
            'codigo' => 'MAT101',
            'nombre' => 'Matemática I',
        ])
        ->assertJsonFragment([
            'codigo' => 'INF101',
            'nombre' => 'Programación I',
        ]);
}


public function test_usuario_sin_autenticacion_no_puede_consultar_perfil(): void
{
    $response = $this->getJson('/api/estudiante/perfil');

    $response->assertStatus(401);
}


public function test_administrador_no_puede_consultar_perfil_estudiantil(): void
{
    $rolAdministrador = Rol::firstOrCreate(
        ['nombre' => 'ADMINISTRADOR'],
        ['descripcion' => 'Administrador del sistema']
    );

    $administrador = Usuario::create([
        'nombre' => 'Admin',
        'apellido' => 'Prueba',
        'username' => 'admin_perfil_test',
        'correo' => 'admin.perfil@example.com',
        'password_hash' => 'hash_de_prueba',
        'estado' => 'ACTIVO',
        'id_rol' => $rolAdministrador->id_rol,
    ]);

    Sanctum::actingAs($administrador);

    $response = $this->getJson('/api/estudiante/perfil');

    $response->assertStatus(403);
}


public function test_estudiante_solo_puede_consultar_su_propio_perfil(): void
{
    Rol::firstOrCreate(
        ['nombre' => 'ESTUDIANTE'],
        ['descripcion' => 'Estudiante del sistema']
    );

    $estudiante1 = Estudiante::create([
        'ci' => '11111111',
        'nombre' => 'Carlos',
        'apellido' => 'Lopez',
        'codigo_universitario' => '202600111',
        'correo' => 'carlos.privado@example.com',
        'estado' => 'ACTIVO',
    ]);

    $estudiante2 = Estudiante::create([
        'ci' => '22222222',
        'nombre' => 'Maria',
        'apellido' => 'Gomez',
        'codigo_universitario' => '202600222',
        'correo' => 'maria.privado@example.com',
        'estado' => 'ACTIVO',
    ]);

    $usuario1 = DB::transaction(function () use ($estudiante1) {
        return app(CuentaEstudianteService::class)
            ->crearCuenta($estudiante1);
    });

    DB::transaction(function () use ($estudiante2) {
        app(CuentaEstudianteService::class)
            ->crearCuenta($estudiante2);
    });

    // Iniciamos sesión únicamente como Carlos.
    Sanctum::actingAs($usuario1);

    $response = $this->getJson('/api/estudiante/perfil');

    // Carlos recibe sus propios datos.
    $response->assertOk()
        ->assertJsonPath('nombre', 'Carlos')
        ->assertJsonPath('codigo_universitario', '202600111');

    // Los datos de Maria no deben aparecer.
    $response->assertJsonMissing([
        'codigo_universitario' => '202600222',
    ]);

    $response->assertJsonMissing([
        'correo' => 'maria.privado@example.com',
    ]);
}

public function test_usuario_rol_estudiante_creado_manualmente_aprovisiona_perfil_sin_bloqueo(): void
{
    $rolEstudiante = Rol::firstOrCreate(
        ['nombre' => 'ESTUDIANTE'],
        ['descripcion' => 'Estudiante del sistema']
    );

    $usuarioManual = Usuario::create([
        'nombre' => 'Jairo',
        'apellido' => 'Rojas',
        'username' => 'Jairo123',
        'correo' => 'jairo@gmail.com',
        'password_hash' => 'hash_de_prueba',
        'estado' => 'ACTIVO',
        'id_rol' => $rolEstudiante->id_rol,
    ]);

    Sanctum::actingAs($usuarioManual);

    $response = $this->getJson('/api/estudiante/perfil');

    $response->assertOk()
        ->assertJsonPath('nombre', 'Jairo')
        ->assertJsonPath('apellido', 'Rojas')
        ->assertJsonPath('correo', 'jairo@gmail.com');

    $this->assertDatabaseHas('estudiante', [
        'id_usuario' => $usuarioManual->id_usuario,
        'correo' => 'jairo@gmail.com',
    ]);
}

}
