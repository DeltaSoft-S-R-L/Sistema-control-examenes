<?php

namespace Tests\Feature;

use App\Models\Permiso;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\TestCase;

class TraslapeAmbienteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Verificar la conexión real a PostgreSQL.
        $conexion = DB::connection();

        $motor = $conexion->getDriverName();

        if ($motor !== 'pgsql') {
            throw new RuntimeException(
                'Pruebas detenidas: el motor no es PostgreSQL.'
            );
        }

        $nombreBase = $conexion->selectOne(
            'SELECT current_database() AS nombre'
        )->nombre;

        if ($nombreBase !== 'control_examenes_pruebas') {
            throw new RuntimeException(
                'Pruebas detenidas: base de datos no autorizada.'
            );
        }

        // Iniciar la transacción después de verificar la base.
        $conexion->beginTransaction();

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
    $this->autenticarUsuarioConPermiso();

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
    $this->autenticarUsuarioConPermiso();

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
    $this->autenticarUsuarioConPermiso();

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
    $this->autenticarUsuarioConPermiso();

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
    private function autenticarUsuarioConPermiso(): void
    {
        $permiso = Permiso::where(
            'nombre',
            'GESTIONAR_EXAMENES'
        )->firstOrFail();

        // Rol simulado, sin guardarlo en la base.
        $rol = new Rol();
        $rol->setRelation('permisos', collect([$permiso]));

        // Usuario simulado, sin guardarlo en la base.
        $usuario = new Usuario();
        $usuario->id_usuario = 1;
        $usuario->setRelation('rol', $rol);

        Sanctum::actingAs($usuario);
    }
}
