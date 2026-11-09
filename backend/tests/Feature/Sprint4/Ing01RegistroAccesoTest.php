<?php

declare(strict_types=1);

namespace Tests\Feature\Sprint4;

use App\Models\Ambiente;
use App\Models\AsignacionAmbiente;
use App\Models\Asignatura;
use App\Models\Carrera;
use App\Models\Estudiante;
use App\Models\Examen;
use App\Models\Facultad;
use App\Models\Habilitacion;
use App\Models\Ingreso;
use App\Models\IngresoExamen;
use App\Models\Rol;
use App\Models\Usuario;
use App\Utilities\PasswordHasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Ing01RegistroAccesoTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $controlador1;
    private string $tokenControlador1;

    private Usuario $controlador2;
    private string $tokenControlador2;

    private Examen $examen;
    private Ambiente $ambiente;
    private AsignacionAmbiente $asignacionAmbiente;

    private Estudiante $estudiante1;
    private Habilitacion $habilitacion1;

    private Estudiante $estudiante2;
    private Habilitacion $habilitacion2;

    protected function setUp(): void
    {
        parent::setUp();

        $rolControl = Rol::create([
            'nombre' => 'CONTROL_INGRESO',
            'descripcion' => 'Personal de control de ingreso',
        ]);

        $this->controlador1 = Usuario::create([
            'nombre' => 'Rodrigo',
            'apellido' => 'Vargas',
            'correo' => 'rodrigo.vargas@umss.edu.bo',
            'username' => 'rodrigo_control',
            'password_hash' => PasswordHasher::hash('ClaveControl123!'),
            'id_rol' => $rolControl->id_rol,
            'estado' => 'ACTIVO',
        ]);
        $this->tokenControlador1 = $this->controlador1->createToken('token-control-1')->plainTextToken;

        $this->controlador2 = Usuario::create([
            'nombre' => 'Elena',
            'apellido' => 'Paredes',
            'correo' => 'elena.paredes@umss.edu.bo',
            'username' => 'elena_control',
            'password_hash' => PasswordHasher::hash('ClaveControl123!'),
            'id_rol' => $rolControl->id_rol,
            'estado' => 'ACTIVO',
        ]);
        $this->tokenControlador2 = $this->controlador2->createToken('token-control-2')->plainTextToken;

        $facultad = Facultad::create([
            'codigo' => 'FCYT',
            'nombre' => 'Facultad de Ciencias y Tecnologia',
        ]);

        $carrera = Carrera::create([
            'codigo' => 'SIS',
            'nombre' => 'Ingenieria de Sistemas',
            'id_facultad' => $facultad->id_facultad,
        ]);

        $asignatura = Asignatura::create([
            'codigo' => 'SIS-200',
            'nombre' => 'Bases de Datos I',
        ]);

        $this->examen = Examen::create([
            'id_asignatura' => $asignatura->id_asignatura,
            'nombre' => 'Examen Final Bases de Datos',
            'fecha' => '2026-11-28',
            'hora_inicio' => '14:00:00',
            'duracion_minutos' => 120,
            'estado' => 'PROGRAMADO',
        ]);

        $this->ambiente = Ambiente::create([
            'codigo' => '692C',
            'nombre' => 'Auditorio 692C',
            'capacidad' => 80,
            'estado' => 'DISPONIBLE',
        ]);

        $this->asignacionAmbiente = AsignacionAmbiente::create([
            'id_examen' => $this->examen->id_examen,
            'id_ambiente' => $this->ambiente->id_ambiente,
        ]);

        $this->estudiante1 = Estudiante::create([
            'ci' => '7112233',
            'nombre' => 'Lucas',
            'apellido' => 'Camacho',
            'codigo_universitario' => '20240091',
            'id_carrera' => $carrera->id_carrera,
            'correo' => 'lucas.camacho@est.umss.edu',
            'estado' => 'ACTIVO',
        ]);

        $this->habilitacion1 = Habilitacion::create([
            'id_estudiante' => $this->estudiante1->id_estudiante,
            'id_examen' => $this->examen->id_examen,
            'id_asignacion_ambiente' => $this->asignacionAmbiente->id_asignacion_ambiente,
            'estado' => 'HABILITADO',
        ]);

        $this->estudiante2 = Estudiante::create([
            'ci' => '7223344',
            'nombre' => 'Camila',
            'apellido' => 'Navarro',
            'codigo_universitario' => '20240092',
            'id_carrera' => $carrera->id_carrera,
            'correo' => 'camila.navarro@est.umss.edu',
            'estado' => 'ACTIVO',
        ]);

        $this->habilitacion2 = Habilitacion::create([
            'id_estudiante' => $this->estudiante2->id_estudiante,
            'id_examen' => $this->examen->id_examen,
            'id_asignacion_ambiente' => $this->asignacionAmbiente->id_asignacion_ambiente,
            'estado' => 'HABILITADO',
        ]);
    }

    public function test_controlador_asignado_es_exactamente_el_que_ejecuto_la_llamada(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenControlador1}",
            'Accept' => 'application/json',
        ])->postJson('/api/ingresos', [
            'id_examen' => $this->examen->id_examen,
            'id_habilitacion' => $this->habilitacion1->id_habilitacion,
            'id_asignacion_ambiente' => $this->asignacionAmbiente->id_asignacion_ambiente,
        ]);

        $response->assertStatus(201);

        $response->assertJson([
            'id_examen' => $this->examen->id_examen,
            'id_habilitacion' => $this->habilitacion1->id_habilitacion,
            'id_asignacion_ambiente' => $this->asignacionAmbiente->id_asignacion_ambiente,
            'id_usuario' => $this->controlador1->id_usuario,
            'controlador_id' => $this->controlador1->id_usuario,
        ]);

        $this->assertDatabaseHas('ingreso_examen', [
            'id_examen' => $this->examen->id_examen,
            'id_habilitacion' => $this->habilitacion1->id_habilitacion,
            'id_asignacion_ambiente' => $this->asignacionAmbiente->id_asignacion_ambiente,
            'id_usuario' => $this->controlador1->id_usuario,
        ]);

        $this->assertDatabaseHas('ingreso', [
            'id_examen' => $this->examen->id_examen,
            'id_habilitacion' => $this->habilitacion1->id_habilitacion,
            'id_asignacion_ambiente' => $this->asignacionAmbiente->id_asignacion_ambiente,
            'id_usuario' => $this->controlador1->id_usuario,
        ]);

        $this->assertDatabaseHas(IngresoExamen::class, [
            'id_habilitacion' => $this->habilitacion1->id_habilitacion,
            'id_usuario' => $this->controlador1->id_usuario,
        ]);

        $this->assertDatabaseMissing('ingreso_examen', [
            'id_habilitacion' => $this->habilitacion1->id_habilitacion,
            'id_usuario' => $this->controlador2->id_usuario,
        ]);
    }

    public function test_cambio_de_controlador_en_segunda_llamada_vincula_al_nuevo_operador(): void
    {
        $res1 = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenControlador1}",
            'Accept' => 'application/json',
        ])->postJson('/api/ingresos', [
            'id_examen' => $this->examen->id_examen,
            'id_habilitacion' => $this->habilitacion1->id_habilitacion,
            'id_asignacion_ambiente' => $this->asignacionAmbiente->id_asignacion_ambiente,
        ]);
        $res1->assertStatus(201);

        $this->app['auth']->forgetGuards();

        $res2 = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenControlador2}",
            'Accept' => 'application/json',
        ])->postJson('/api/ingresos', [
            'id_examen' => $this->examen->id_examen,
            'id_habilitacion' => $this->habilitacion2->id_habilitacion,
            'id_asignacion_ambiente' => $this->asignacionAmbiente->id_asignacion_ambiente,
        ]);
        $res2->assertStatus(201);

        $this->assertDatabaseHas('ingreso_examen', [
            'id_habilitacion' => $this->habilitacion1->id_habilitacion,
            'id_usuario' => $this->controlador1->id_usuario,
        ]);

        $this->assertDatabaseHas('ingreso_examen', [
            'id_habilitacion' => $this->habilitacion2->id_habilitacion,
            'id_usuario' => $this->controlador2->id_usuario,
        ]);

        $this->assertSame(2, IngresoExamen::count());
    }

    public function test_persistencia_transaccional_en_tabla_ingreso_examen_garantiza_integridad_completa(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenControlador1}",
            'Accept' => 'application/json',
        ])->postJson('/api/ingresos', [
            'id_examen' => $this->examen->id_examen,
            'id_habilitacion' => $this->habilitacion1->id_habilitacion,
            'id_asignacion_ambiente' => $this->asignacionAmbiente->id_asignacion_ambiente,
        ]);

        $response->assertStatus(201);
        $idIngreso = $response->json('id_ingreso') ?? $response->json('id');
        $this->assertNotNull($idIngreso);

        $ingresoModel = IngresoExamen::with(['usuario', 'habilitacion.estudiante', 'examen', 'ambiente'])->find($idIngreso);
        $this->assertNotNull($ingresoModel);
        $this->assertSame($this->controlador1->id_usuario, $ingresoModel->id_usuario);
        $this->assertSame($this->controlador1->id_usuario, $ingresoModel->usuario->id_usuario);
        $this->assertSame($this->estudiante1->id_estudiante, $ingresoModel->estudiante->id_estudiante);
        $this->assertSame($this->ambiente->id_ambiente, $ingresoModel->ambiente->id_ambiente);
        $this->assertSame($this->examen->id_examen, $ingresoModel->examen->id_examen);
    }

    public function test_cliente_no_puede_suplantar_controlador_enviando_otro_id_usuario_en_payload(): void
    {
        $idFalso = 9999;

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenControlador1}",
            'Accept' => 'application/json',
        ])->postJson('/api/ingresos', [
            'id_examen' => $this->examen->id_examen,
            'id_habilitacion' => $this->habilitacion1->id_habilitacion,
            'id_asignacion_ambiente' => $this->asignacionAmbiente->id_asignacion_ambiente,
            'id_usuario' => $idFalso,
            'controlador_id' => $idFalso,
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'id_usuario' => $this->controlador1->id_usuario,
            'controlador_id' => $this->controlador1->id_usuario,
        ]);

        $this->assertDatabaseMissing('ingreso_examen', [
            'id_usuario' => $idFalso,
        ]);

        $this->assertDatabaseHas('ingreso_examen', [
            'id_habilitacion' => $this->habilitacion1->id_habilitacion,
            'id_usuario' => $this->controlador1->id_usuario,
        ]);
    }

    public function test_persistencia_transaccional_revierte_si_ocurre_falla_de_integridad(): void
    {
        $this->habilitacion1->update(['estado' => 'NO_HABILITADO']);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->tokenControlador1}",
            'Accept' => 'application/json',
        ])->postJson('/api/ingresos', [
            'id_examen' => $this->examen->id_examen,
            'id_habilitacion' => $this->habilitacion1->id_habilitacion,
            'id_asignacion_ambiente' => $this->asignacionAmbiente->id_asignacion_ambiente,
        ]);

        $response->assertStatus(403);
        $this->assertSame(0, IngresoExamen::count());
        $this->assertSame(0, Ingreso::count());
    }
}
