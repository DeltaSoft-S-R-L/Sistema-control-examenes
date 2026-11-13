<?php

declare(strict_types=1);

namespace Tests\Feature\Sprint4;

use App\Models\Ambiente;
use App\Models\AsignacionAmbiente;
use App\Models\Asignatura;
use App\Models\AuditLog;
use App\Models\Carrera;
use App\Models\Estudiante;
use App\Models\Examen;
use App\Models\Facultad;
use App\Models\Habilitacion;
use App\Models\Ingreso;
use App\Models\IntentoIngreso;
use App\Models\Rol;
use App\Models\Usuario;
use App\Utilities\PasswordHasher;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Ing03DobleIngresoTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $controlUser;
    private string $controlToken;

    private Examen $examen;
    private Ambiente $ambienteA;
    private Ambiente $ambienteB;
    private AsignacionAmbiente $asignacionAmbienteA;
    private AsignacionAmbiente $asignacionAmbienteB;

    private Estudiante $estudianteHabilitado;
    private Habilitacion $habilitacionValida;
    private Carrera $carrera;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Rol y Usuario Operador de Control de Ingreso
        $rolControl = Rol::create([
            'nombre'      => 'CONTROL_INGRESO',
            'descripcion' => 'Personal de control de ingreso en puerta',
        ]);

        $this->controlUser = Usuario::create([
            'nombre'        => 'Carlos',
            'apellido'      => 'Pinto',
            'correo'        => 'carlos.control@umss.edu.bo',
            'username'      => 'carlos_control',
            'password_hash' => PasswordHasher::hash('ControlPass123!'),
            'id_rol'        => $rolControl->id_rol,
            'estado'        => 'ACTIVO',
        ]);
        $this->controlToken = $this->controlUser->createToken('token-control-ingreso')->plainTextToken;

        // 2. Estructura Académica
        $facultad = Facultad::create([
            'codigo' => 'FCYT',
            'nombre' => 'Facultad de Ciencias y Tecnología',
        ]);

        $this->carrera = Carrera::create([
            'codigo'      => 'SIS',
            'nombre'      => 'Ingeniería de Sistemas',
            'id_facultad' => $facultad->id_facultad,
        ]);

        $asignatura = Asignatura::create([
            'codigo'      => 'SIS-101',
            'nombre'      => 'Programación I',
            'descripcion' => 'Primer semestre',
        ]);

        // 3. Examen
        $this->examen = Examen::create([
            'id_asignatura'    => $asignatura->id_asignatura,
            'nombre'           => 'Examen Parcial Programación I',
            'fecha'            => '2026-11-20',
            'hora_inicio'      => '08:00:00',
            'duracion_minutos' => 90,
            'estado'           => 'PROGRAMADO',
        ]);

        // 4. Ambientes y Asignaciones al Examen
        $this->ambienteA = Ambiente::create([
            'codigo'    => '624',
            'nombre'    => 'Aula 624',
            'ubicacion' => 'Edificio Nuevo',
            'capacidad' => 50,
            'estado'    => 'DISPONIBLE',
        ]);

        $this->ambienteB = Ambiente::create([
            'codigo'    => '691B',
            'nombre'    => 'Laboratorio 691B',
            'ubicacion' => 'Edificio Central',
            'capacidad' => 40,
            'estado'    => 'DISPONIBLE',
        ]);

        $this->asignacionAmbienteA = AsignacionAmbiente::create([
            'id_examen'   => $this->examen->id_examen,
            'id_ambiente' => $this->ambienteA->id_ambiente,
        ]);

        $this->asignacionAmbienteB = AsignacionAmbiente::create([
            'id_examen'   => $this->examen->id_examen,
            'id_ambiente' => $this->ambienteB->id_ambiente,
        ]);

        // 5. Estudiante y Habilitación Válida
        $this->estudianteHabilitado = Estudiante::create([
            'ci'                   => '7712345',
            'nombre'               => 'Ana',
            'apellido'             => 'Torres',
            'codigo_universitario' => '202400010',
            'id_carrera'           => $this->carrera->id_carrera,
            'correo'               => 'ana.torres@umss.edu.bo',
            'estado'               => 'ACTIVO',
        ]);

        $this->habilitacionValida = Habilitacion::create([
            'id_estudiante' => $this->estudianteHabilitado->id_estudiante,
            'id_examen'     => $this->examen->id_examen,
            'estado'        => 'HABILITADO',
        ]);
    }

    /**
     * Escenario 1: El primer ingreso válido se registra con 201 Created.
     * Cualquier segundo intento sobre la misma habilitación retorna 409 Conflict (REINGRESO_NO_PERMITIDO)
     * y la base de datos mantiene estrictamente 1 único registro.
     */
    public function test_primer_ingreso_exitoso_y_segundo_intento_retorna_409_conflict(): void
    {
        // 1. Primer ingreso exitoso
        $response1 = $this->withHeaders([
            'Authorization' => "Bearer {$this->controlToken}",
            'Accept'        => 'application/json',
        ])->postJson('/api/ingresos', [
            'id_examen'              => $this->examen->id_examen,
            'id_habilitacion'        => $this->habilitacionValida->id_habilitacion,
            'id_asignacion_ambiente' => $this->asignacionAmbienteA->id_asignacion_ambiente,
        ]);

        $response1->assertStatus(201)
            ->assertJson([
                'id_examen'              => $this->examen->id_examen,
                'id_habilitacion'        => $this->habilitacionValida->id_habilitacion,
                'id_asignacion_ambiente' => $this->asignacionAmbienteA->id_asignacion_ambiente,
                'id_usuario'             => $this->controlUser->id_usuario,
            ]);

        $this->assertDatabaseCount('ingreso', 1);

        // 2. Segundo intento inmediato con la misma habilitación
        $response2 = $this->withHeaders([
            'Authorization' => "Bearer {$this->controlToken}",
            'Accept'        => 'application/json',
        ])->postJson('/api/ingresos', [
            'id_examen'              => $this->examen->id_examen,
            'id_habilitacion'        => $this->habilitacionValida->id_habilitacion,
            'id_asignacion_ambiente' => $this->asignacionAmbienteA->id_asignacion_ambiente,
        ]);

        $response2->assertStatus(409)
            ->assertJson([
                'message'  => 'El estudiante ya registró su ingreso a este examen.',
                'code'     => 'REINGRESO_NO_PERMITIDO',
                'error'    => 'REINGRESO_NO_PERMITIDO',
                'ambiente' => $this->ambienteA->nombre,
            ])
            ->assertJsonStructure([
                'message',
                'code',
                'error',
                'primer_ingreso',
                'ambiente',
            ]);

        // 3. Tercer intento repetido para certificar idempotencia en la respuesta de bloqueo
        $response3 = $this->withHeaders([
            'Authorization' => "Bearer {$this->controlToken}",
            'Accept'        => 'application/json',
        ])->postJson('/api/ingresos', [
            'id_examen'              => $this->examen->id_examen,
            'id_habilitacion'        => $this->habilitacionValida->id_habilitacion,
            'id_asignacion_ambiente' => $this->asignacionAmbienteA->id_asignacion_ambiente,
        ]);

        $response3->assertStatus(409)
            ->assertJson([
                'code'  => 'REINGRESO_NO_PERMITIDO',
                'error' => 'REINGRESO_NO_PERMITIDO',
            ]);

        // 4. Verificar que sigue existiendo exactamente 1 registro en la tabla ingreso
        $this->assertDatabaseCount('ingreso', 1);
        $this->assertEquals(1, Ingreso::where('id_habilitacion', $this->habilitacionValida->id_habilitacion)->count());
    }

    /**
     * Escenario 2: Intento de reingreso en diferente aula del mismo examen.
     * Se registra primer ingreso en Aula A. Un segundo intento enviando el ambiente de Aula B
     * debe ser bloqueado con 409 Conflict, indicando que el primer ingreso fue en Aula A.
     */
    public function test_intento_de_reingreso_en_diferente_aula_del_mismo_examen_es_bloqueado(): void
    {
        // 1. Primer ingreso en Aula A
        $response1 = $this->withHeaders([
            'Authorization' => "Bearer {$this->controlToken}",
            'Accept'        => 'application/json',
        ])->postJson('/api/ingresos', [
            'id_examen'              => $this->examen->id_examen,
            'id_habilitacion'        => $this->habilitacionValida->id_habilitacion,
            'id_asignacion_ambiente' => $this->asignacionAmbienteA->id_asignacion_ambiente,
        ]);
        $response1->assertStatus(201);

        // 2. Segundo intento en Aula B (asignacionAmbienteB)
        $response2 = $this->withHeaders([
            'Authorization' => "Bearer {$this->controlToken}",
            'Accept'        => 'application/json',
        ])->postJson('/api/ingresos', [
            'id_examen'              => $this->examen->id_examen,
            'id_habilitacion'        => $this->habilitacionValida->id_habilitacion,
            'id_asignacion_ambiente' => $this->asignacionAmbienteB->id_asignacion_ambiente,
        ]);

        $response2->assertStatus(409)
            ->assertJson([
                'message'  => 'El estudiante ya registró su ingreso a este examen.',
                'code'     => 'REINGRESO_NO_PERMITIDO',
                'error'    => 'REINGRESO_NO_PERMITIDO',
                'ambiente' => $this->ambienteA->nombre,
            ])
            ->assertJsonStructure([
                'message',
                'code',
                'error',
                'primer_ingreso',
                'ambiente',
            ]);

        // Verificamos que no se haya modificado ni duplicado el ingreso en la base de datos
        $this->assertDatabaseCount('ingreso', 1);
        $ingresoDb = Ingreso::first();
        $this->assertEquals($this->asignacionAmbienteA->id_asignacion_ambiente, $ingresoDb->id_asignacion_ambiente);
    }

    /**
     * Escenario 3: Cada intento de reingreso bloqueado se audita en intento_ingreso y audit_logs.
     */
    public function test_intento_de_reingreso_registra_anomalia_en_intento_ingreso_y_audit_logs(): void
    {
        // 1. Primer ingreso exitoso
        $this->withHeaders([
            'Authorization' => "Bearer {$this->controlToken}",
            'Accept'        => 'application/json',
        ])->postJson('/api/ingresos', [
            'id_examen'              => $this->examen->id_examen,
            'id_habilitacion'        => $this->habilitacionValida->id_habilitacion,
            'id_asignacion_ambiente' => $this->asignacionAmbienteA->id_asignacion_ambiente,
        ])->assertStatus(201);

        // 2. Intento de reingreso no permitido (409)
        $this->withHeaders([
            'Authorization' => "Bearer {$this->controlToken}",
            'Accept'        => 'application/json',
        ])->postJson('/api/ingresos', [
            'id_examen'              => $this->examen->id_examen,
            'id_habilitacion'        => $this->habilitacionValida->id_habilitacion,
            'id_asignacion_ambiente' => $this->asignacionAmbienteA->id_asignacion_ambiente,
        ])->assertStatus(409);

        // 3. Verificación de intento_ingreso
        $this->assertDatabaseHas('intento_ingreso', [
            'id_estudiante' => $this->estudianteHabilitado->id_estudiante,
            'id_examen'     => $this->examen->id_examen,
            'id_usuario'    => $this->controlUser->id_usuario,
            'motivo'        => 'REINGRESO_DUPLICADO',
        ]);

        // 4. Verificación de audit_logs
        $this->assertDatabaseHas('audit_logs', [
            'accion'     => 'INTENTO_REINGRESO_DUPLICADO',
            'entidad'    => 'ingreso',
            'id_usuario' => $this->controlUser->id_usuario,
        ]);
    }

    /**
     * Escenario 4: La restricción física UNIQUE(id_habilitacion) y el controlador
     * previenen duplicados ante concurrencia o inserción directa.
     */
    public function test_restriccion_unica_en_base_de_datos_impide_duplicados_a_nivel_fisico_o_concurrente(): void
    {
        // 1. Inserción del primer ingreso directamente en base de datos
        $primerIngreso = Ingreso::create([
            'id_examen'              => $this->examen->id_examen,
            'id_habilitacion'        => $this->habilitacionValida->id_habilitacion,
            'id_asignacion_ambiente' => $this->asignacionAmbienteA->id_asignacion_ambiente,
            'id_usuario'             => $this->controlUser->id_usuario,
            'fecha_hora'             => now()->subMinutes(10),
        ]);

        // 2. Intento de forzar un segundo ingreso directo con el mismo id_habilitacion
        $capturedException = null;
        try {
            Ingreso::create([
                'id_examen'              => $this->examen->id_examen,
                'id_habilitacion'        => $this->habilitacionValida->id_habilitacion,
                'id_asignacion_ambiente' => $this->asignacionAmbienteB->id_asignacion_ambiente,
                'id_usuario'             => $this->controlUser->id_usuario,
                'fecha_hora'             => now(),
            ]);
        } catch (QueryException $e) {
            $capturedException = $e;
        }

        $this->assertInstanceOf(QueryException::class, $capturedException);
        $this->assertDatabaseCount('ingreso', 1);

        // 3. Verificamos que el controlador gestione la concurrencia retornando 409 y nunca 500
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->controlToken}",
            'Accept'        => 'application/json',
        ])->postJson('/api/ingresos', [
            'id_examen'              => $this->examen->id_examen,
            'id_habilitacion'        => $this->habilitacionValida->id_habilitacion,
            'id_asignacion_ambiente' => $this->asignacionAmbienteA->id_asignacion_ambiente,
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'code'                      => 'REINGRESO_NO_PERMITIDO',
                'error'                     => 'REINGRESO_NO_PERMITIDO',
                'primer_ingreso'            => $primerIngreso->fecha_hora->format('H:i:s'),
                'fecha_hora_primer_ingreso' => $primerIngreso->fecha_hora->toIso8601String(),
                'ambiente'                  => $this->ambienteA->nombre,
            ]);

        $this->assertDatabaseCount('ingreso', 1);
    }

    /**
     * Escenario 5: Un mismo estudiante habilitado en Examen 1 y Examen 2 (diferentes id_habilitacion)
     * puede ingresar a ambos exámenes sin falsos positivos de bloqueo.
     */
    public function test_estudiante_puede_ingresar_a_dos_examenes_distintos_sin_bloqueo_falso(): void
    {
        // 1. Crear segundo examen y su asignación de ambiente
        $asignatura2 = Asignatura::create([
            'codigo'      => 'SIS-102',
            'nombre'      => 'Base de Datos I',
            'descripcion' => 'Segundo semestre',
        ]);

        $examen2 = Examen::create([
            'id_asignatura'    => $asignatura2->id_asignatura,
            'nombre'           => 'Examen Parcial Base de Datos I',
            'fecha'            => '2026-11-25',
            'hora_inicio'      => '10:00:00',
            'duracion_minutos' => 90,
            'estado'           => 'PROGRAMADO',
        ]);

        $asignacionAmbienteExamen2 = AsignacionAmbiente::create([
            'id_examen'   => $examen2->id_examen,
            'id_ambiente' => $this->ambienteA->id_ambiente,
        ]);

        $habilitacionExamen2 = Habilitacion::create([
            'id_estudiante' => $this->estudianteHabilitado->id_estudiante,
            'id_examen'     => $examen2->id_examen,
            'estado'        => 'HABILITADO',
        ]);

        // 2. Ingreso al Examen 1 -> 201 Created
        $responseExamen1 = $this->withHeaders([
            'Authorization' => "Bearer {$this->controlToken}",
            'Accept'        => 'application/json',
        ])->postJson('/api/ingresos', [
            'id_examen'              => $this->examen->id_examen,
            'id_habilitacion'        => $this->habilitacionValida->id_habilitacion,
            'id_asignacion_ambiente' => $this->asignacionAmbienteA->id_asignacion_ambiente,
        ]);
        $responseExamen1->assertStatus(201);

        // 3. Ingreso al Examen 2 -> 201 Created (no debe bloquearse porque es otro examen)
        $responseExamen2 = $this->withHeaders([
            'Authorization' => "Bearer {$this->controlToken}",
            'Accept'        => 'application/json',
        ])->postJson('/api/ingresos', [
            'id_examen'              => $examen2->id_examen,
            'id_habilitacion'        => $habilitacionExamen2->id_habilitacion,
            'id_asignacion_ambiente' => $asignacionAmbienteExamen2->id_asignacion_ambiente,
        ]);
        $responseExamen2->assertStatus(201);

        // 4. Verificación en base de datos: existen exactamente 2 ingresos válidos
        $this->assertDatabaseCount('ingreso', 2);
        $this->assertDatabaseHas('ingreso', [
            'id_habilitacion' => $this->habilitacionValida->id_habilitacion,
            'id_examen'       => $this->examen->id_examen,
        ]);
        $this->assertDatabaseHas('ingreso', [
            'id_habilitacion' => $habilitacionExamen2->id_habilitacion,
            'id_examen'       => $examen2->id_examen,
        ]);

        // 5. Y si ahora intenta reingresar a cualquiera de los dos, se bloquea con 409
        $responseReingreso = $this->withHeaders([
            'Authorization' => "Bearer {$this->controlToken}",
            'Accept'        => 'application/json',
        ])->postJson('/api/ingresos', [
            'id_examen'              => $examen2->id_examen,
            'id_habilitacion'        => $habilitacionExamen2->id_habilitacion,
            'id_asignacion_ambiente' => $asignacionAmbienteExamen2->id_asignacion_ambiente,
        ]);
        $responseReingreso->assertStatus(409)
            ->assertJson([
                'code'  => 'REINGRESO_NO_PERMITIDO',
                'error' => 'REINGRESO_NO_PERMITIDO',
            ]);
    }
}
