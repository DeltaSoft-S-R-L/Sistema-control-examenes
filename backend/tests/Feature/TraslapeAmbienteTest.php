<?php

namespace Tests\Feature;

use App\Models\Carrera;
use App\Models\Facultad;
use App\Models\Rol;
use App\Models\Usuario;
use App\Utilities\PasswordHasher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TraslapeAmbienteTest extends TestCase
{
    private int $idCarrera;

    protected function setUp(): void
    {
        parent::setUp();

        // Verificar la conexión real a PostgreSQL.
        $conexion = DB::connection();

        $motor = $conexion->getDriverName();

        if ($motor !== 'pgsql') {
            $this->markTestSkipped('Esta prueba requiere PostgreSQL.');
        }

        $nombreBase = $conexion->selectOne(
            'SELECT current_database() AS nombre'
        )->nombre;

        if ($nombreBase !== 'control_examenes_pruebas') {
            $this->markTestSkipped('Esta prueba solo usa control_examenes_pruebas.');
        }

        // Iniciar la transacción después de verificar la base.
        $conexion->beginTransaction();

        // Cada prueba prepara las referencias que consume; no depende de seeders.
        DB::table('asignatura')->updateOrInsert(
            ['id_asignatura' => 1],
            ['codigo' => 'TEST-TRASLAPE', 'nombre' => 'Asignatura de prueba']
        );
        DB::table('ambiente')->updateOrInsert(
            ['id_ambiente' => 1],
            ['codigo' => 'TEST-TRASLAPE-1', 'nombre' => 'Aula de prueba 1', 'capacidad' => 30, 'estado' => 'DISPONIBLE']
        );
        DB::table('ambiente')->updateOrInsert(
            ['id_ambiente' => 2],
            ['codigo' => 'TEST-TRASLAPE-2', 'nombre' => 'Aula de prueba 2', 'capacidad' => 30, 'estado' => 'DISPONIBLE']
        );

        $sufijo = strtolower(Str::random(8));
        $facultad = Facultad::create([
            'codigo' => 'FTR' . $sufijo,
            'nombre' => 'Facultad de prueba de traslapes',
        ]);
        $carrera = Carrera::create([
            'id_facultad' => $facultad->id_facultad,
            'codigo' => 'CTR' . $sufijo,
            'nombre' => 'Carrera de prueba de traslapes',
        ]);
        $this->idCarrera = $carrera->id_carrera;
        DB::table('carrera_asignatura')->insert([
            'id_carrera' => $this->idCarrera,
            'id_asignatura' => 1,
        ]);

        // Revertir los cambios al finalizar cada prueba.
        $this->beforeApplicationDestroyed(function () use ($conexion) {
            if ($conexion->transactionLevel() > 0) {
                $conexion->rollBack();
            }
        });
    }

    public function test_conexion_segura_a_base_de_pruebas(): void
    {
        $nombreBase = DB::selectOne(
            'SELECT current_database() AS nombre'
        )->nombre;

        $this->assertSame(
            'control_examenes_pruebas',
            $nombreBase
        );
    }

    public function test_rechaza_examen_con_horario_traslapado(): void
{
    $this->autenticarDocenteAsignado();

    $fecha = now()->addDays(7)->format('Y-m-d');

    // Crear un examen temporal de referencia.
    $examen = \App\Models\Examen::create([
        'id_asignatura' => 1,
        'nombre' => 'Examen A - Prueba de traslape',
        'fecha' => $fecha,
        'hora_inicio' => '08:00',
        'duracion_minutos' => 120,
        'estado' => 'PROGRAMADO',
    ]);

    \App\Models\AsignacionAmbiente::create([
        'id_examen' => $examen->id_examen,
        'id_ambiente' => 1,
    ]);

    // Intentar crear otro examen en el mismo ambiente.
    $respuesta = $this->postJson('/api/examenes', [
        'id_asignatura' => 1,
        'nombre' => 'Examen B - Conflicto',
        'fecha' => $fecha,
        'hora_inicio' => '09:00',
        'duracion_minutos' => 120,
        'estado' => 'PROGRAMADO',
        'id_ambiente' => 1,
    ]);

    $respuesta->assertStatus(422)
        ->assertJsonValidationErrors(['id_ambiente']);
}

public function test_permite_examenes_con_horarios_consecutivos(): void
{
    $this->autenticarDocenteAsignado();

    $fecha = now()->addDays(8)->format('Y-m-d');

    $examen = \App\Models\Examen::create([
        'id_asignatura' => 1,
        'nombre' => 'Examen A - Horario consecutivo',
        'fecha' => $fecha,
        'hora_inicio' => '08:00',
        'duracion_minutos' => 120,
        'estado' => 'PROGRAMADO',
    ]);

    \App\Models\AsignacionAmbiente::create([
        'id_examen' => $examen->id_examen,
        'id_ambiente' => 1,
    ]);

    $respuesta = $this->postJson('/api/examenes', [
        'id_asignatura' => 1,
        'nombre' => 'Examen B - Horario consecutivo',
        'fecha' => $fecha,
        'hora_inicio' => '10:00',
        'duracion_minutos' => 120,
        'estado' => 'PROGRAMADO',
        'id_ambiente' => 1,
    ]);

    $respuesta->assertStatus(201)
        ->assertJsonPath('message', 'Examen creado correctamente.');
}

public function test_permite_mismo_horario_en_ambientes_diferentes(): void
{
    $this->autenticarDocenteAsignado();

    $fecha = now()->addDays(9)->format('Y-m-d');

    $examen = \App\Models\Examen::create([
        'id_asignatura' => 1,
        'nombre' => 'Examen A - Ambiente 1',
        'fecha' => $fecha,
        'hora_inicio' => '08:00',
        'duracion_minutos' => 120,
        'estado' => 'PROGRAMADO',
    ]);

    \App\Models\AsignacionAmbiente::create([
        'id_examen' => $examen->id_examen,
        'id_ambiente' => 1,
    ]);

    $respuesta = $this->postJson('/api/examenes', [
        'id_asignatura' => 1,
        'nombre' => 'Examen B - Ambiente 2',
        'fecha' => $fecha,
        'hora_inicio' => '08:00',
        'duracion_minutos' => 120,
        'estado' => 'PROGRAMADO',
        'id_ambiente' => 2,
    ]);

    $respuesta->assertStatus(201);
}

public function test_rechaza_traslape_al_actualizar_examen(): void
{
    $this->autenticarDocenteAsignado();

    $fecha = now()->addDays(10)->format('Y-m-d');

    // Examen A: 08:00 a 10:00.
    $examenA = \App\Models\Examen::create([
        'id_asignatura' => 1,
        'nombre' => 'Examen A - Actualizacion',
        'fecha' => $fecha,
        'hora_inicio' => '08:00',
        'duracion_minutos' => 120,
        'estado' => 'PROGRAMADO',
    ]);

    \App\Models\AsignacionAmbiente::create([
        'id_examen' => $examenA->id_examen,
        'id_ambiente' => 1,
    ]);

    // Examen B: 11:00 a 13:00, inicialmente sin conflicto.
    $examenB = \App\Models\Examen::create([
        'id_asignatura' => 1,
        'nombre' => 'Examen B - Actualizacion',
        'fecha' => $fecha,
        'hora_inicio' => '11:00',
        'duracion_minutos' => 120,
        'estado' => 'PROGRAMADO',
    ]);

    \App\Models\AsignacionAmbiente::create([
        'id_examen' => $examenB->id_examen,
        'id_ambiente' => 1,
    ]);

    // Intentar cambiar el examen B a las 09:00.
    $respuesta = $this->putJson(
        '/api/examenes/' . $examenB->id_examen,
        [
            'hora_inicio' => '09:00',
        ]
    );

    $respuesta->assertStatus(422)
        ->assertJsonValidationErrors(['id_ambiente']);
}
    private function autenticarDocenteAsignado(): void
    {
        $sufijo = strtolower(Str::random(8));
        $rol = Rol::firstOrCreate(
            ['nombre' => 'DOCENTE'],
            ['descripcion' => 'Docente']
        );

        $usuario = Usuario::create([
            'nombre' => 'Docente',
            'apellido' => 'Prueba',
            'correo' => "traslape_{$sufijo}@example.test",
            'username' => "traslape_{$sufijo}",
            'password_hash' => PasswordHasher::hash('Password123'),
            'id_rol' => $rol->id_rol,
            'estado' => 'ACTIVO',
        ]);

        DB::table('docente_asignatura')->insert([
            'id_usuario' => $usuario->id_usuario,
            'id_carrera' => $this->idCarrera,
            'id_asignatura' => 1,
        ]);

        Sanctum::actingAs($usuario);
    }
}
