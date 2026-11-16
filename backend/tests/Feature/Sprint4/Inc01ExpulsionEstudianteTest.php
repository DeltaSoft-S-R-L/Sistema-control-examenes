<?php

declare(strict_types=1);

namespace Tests\Feature\Sprint4;

use App\Models\Ambiente;
use App\Models\AsignacionAmbiente;
use App\Models\Asignatura;
use App\Models\Carrera;
use App\Models\Estudiante;
use App\Models\Examen;
use App\Models\Expulsion;
use App\Models\Facultad;
use App\Models\Habilitacion;
use App\Models\Ingreso;
use App\Models\Rol;
use App\Models\Usuario;
use App\Services\HabilitacionService;
use App\Utilities\PasswordHasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Inc01ExpulsionEstudianteTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $operador;
    private string $token;
    private Examen $examen;
    private Estudiante $estudiante;
    private Habilitacion $habilitacion;
    private Ingreso $ingreso;

    protected function setUp(): void
    {
        parent::setUp();
        $rol = Rol::create(['nombre' => 'CONTROL_INGRESO', 'descripcion' => 'Control de ingreso']);
        $this->operador = Usuario::create([
            'nombre' => 'Control', 'apellido' => 'Prueba', 'correo' => 'control.expulsion@test.bo',
            'username' => 'control_expulsion', 'password_hash' => PasswordHasher::hash('Clave123!'),
            'id_rol' => $rol->id_rol, 'estado' => 'ACTIVO',
        ]);
        $this->token = $this->operador->createToken('test-expulsion')->plainTextToken;
        $facultad = Facultad::create(['codigo' => 'FCT', 'nombre' => 'Facultad de Tecnología']);
        $carrera = Carrera::create(['codigo' => 'SIS', 'nombre' => 'Sistemas', 'id_facultad' => $facultad->id_facultad]);
        $asignatura = Asignatura::create(['codigo' => 'SIS-101', 'nombre' => 'Programación']);
        $this->examen = Examen::create(['id_asignatura' => $asignatura->id_asignatura, 'nombre' => 'Parcial', 'fecha' => '2026-11-20', 'hora_inicio' => '09:00:00', 'duracion_minutos' => 90, 'estado' => 'PROGRAMADO']);
        $ambiente = Ambiente::create(['codigo' => 'A-1', 'nombre' => 'Aula 1', 'capacidad' => 30, 'estado' => 'DISPONIBLE']);
        $asignacion = AsignacionAmbiente::create(['id_examen' => $this->examen->id_examen, 'id_ambiente' => $ambiente->id_ambiente]);
        $this->estudiante = Estudiante::create(['ci' => '1234567', 'nombre' => 'Ana', 'apellido' => 'Pérez', 'codigo_universitario' => '20240001', 'id_carrera' => $carrera->id_carrera, 'correo' => 'ana@test.bo', 'estado' => 'ACTIVO']);
        $this->habilitacion = Habilitacion::create(['id_estudiante' => $this->estudiante->id_estudiante, 'id_examen' => $this->examen->id_examen, 'id_asignacion_ambiente' => $asignacion->id_asignacion_ambiente, 'estado' => 'HABILITADO']);
        $this->ingreso = Ingreso::create(['id_examen' => $this->examen->id_examen, 'id_habilitacion' => $this->habilitacion->id_habilitacion, 'id_asignacion_ambiente' => $asignacion->id_asignacion_ambiente, 'id_usuario' => $this->operador->id_usuario]);
    }

    private function conToken(): static
    {
        return $this->withHeader('Authorization', "Bearer {$this->token}");
    }

    public function test_registro_crea_expulsion_incidencia_cambia_estado_y_audita(): void
    {
        $this->conToken()->postJson("/api/examenes/{$this->examen->id_examen}/expulsiones", [
            'id_estudiante' => $this->estudiante->id_estudiante, 'motivo' => 'INTENTO_COPIA',
        ])->assertCreated()->assertJsonPath('motivo', 'INTENTO_COPIA');

        $this->assertDatabaseHas('expulsion', ['id_ingreso' => $this->ingreso->id_ingreso, 'motivo' => 'INTENTO_COPIA']);
        $this->assertDatabaseHas('incidencia', ['id_estudiante' => $this->estudiante->id_estudiante, 'tipo' => 'INTENTO_COPIA']);
        $this->assertDatabaseHas('habilitacion', ['id_habilitacion' => $this->habilitacion->id_habilitacion, 'estado' => 'EXPULSADO']);
        $this->assertDatabaseHas('audit_logs', ['accion' => 'REGISTRO_EXPULSION_ESTUDIANTE']);
        self::assertSame('EXPULSADO', HabilitacionService::verificarHabilitacion($this->estudiante, $this->examen)['estado']);
    }

    public function test_rechaza_falta_de_ingreso_motivo_invalido_y_otro_sin_detalle(): void
    {
        $sinIngreso = Estudiante::create(['ci' => '7654321', 'nombre' => 'Luis', 'apellido' => 'Prueba', 'codigo_universitario' => '20240002', 'id_carrera' => $this->estudiante->id_carrera, 'correo' => 'luis@test.bo', 'estado' => 'ACTIVO']);
        Habilitacion::create(['id_estudiante' => $sinIngreso->id_estudiante, 'id_examen' => $this->examen->id_examen, 'id_asignacion_ambiente' => $this->habilitacion->id_asignacion_ambiente, 'estado' => 'HABILITADO']);
        $this->conToken()->postJson("/api/examenes/{$this->examen->id_examen}/expulsiones", ['id_estudiante' => $sinIngreso->id_estudiante, 'motivo' => 'INTENTO_COPIA'])->assertUnprocessable()->assertJsonPath('code', 'ESTUDIANTE_SIN_INGRESO');
        $this->conToken()->postJson("/api/examenes/{$this->examen->id_examen}/expulsiones", ['id_ingreso' => $this->ingreso->id_ingreso, 'motivo' => 'MOTIVO_INVALIDO'])->assertUnprocessable();
        $this->conToken()->postJson("/api/examenes/{$this->examen->id_examen}/expulsiones", ['id_ingreso' => $this->ingreso->id_ingreso, 'motivo' => 'OTRO'])->assertUnprocessable();
    }

    public function test_una_segunda_expulsion_retoma_conflicto_y_no_duplica(): void
    {
        $payload = ['id_ingreso' => $this->ingreso->id_ingreso, 'motivo' => 'INTENTO_COPIA'];
        $this->conToken()->postJson("/api/examenes/{$this->examen->id_examen}/expulsiones", $payload)->assertCreated();
        $this->conToken()->postJson("/api/examenes/{$this->examen->id_examen}/expulsiones", $payload)->assertStatus(409)->assertJsonPath('code', 'ESTUDIANTE_YA_EXPULSADO');
        $this->assertSame(1, Expulsion::count());
    }

    public function test_expone_el_estado_y_detalle_en_el_listado_de_ingresos(): void
    {
        $this->conToken()->postJson("/api/examenes/{$this->examen->id_examen}/expulsiones", ['id_ingreso' => $this->ingreso->id_ingreso, 'motivo' => 'OTRO', 'descripcion' => 'Observación detallada'])->assertCreated();
        $this->conToken()->getJson("/api/ingresos?id_examen={$this->examen->id_examen}")->assertOk()->assertJsonPath('data.0.estado', 'EXPULSADO')->assertJsonPath('data.0.expulsion.descripcion', 'Observación detallada');
    }

    public function test_requiere_autenticacion_y_rol_autorizado(): void
    {
        $url = "/api/examenes/{$this->examen->id_examen}/expulsiones";
        $this->postJson($url, ['id_ingreso' => $this->ingreso->id_ingreso, 'motivo' => 'INTENTO_COPIA'])->assertUnauthorized();
        $rol = Rol::where('nombre', 'ESTUDIANTE')->firstOrFail();
        $usuario = Usuario::create(['nombre' => 'Estudiante', 'apellido' => 'Test', 'correo' => 'est@test.bo', 'username' => 'est_expulsion', 'password_hash' => PasswordHasher::hash('Clave123!'), 'id_rol' => $rol->id_rol, 'estado' => 'ACTIVO']);
        $this->withHeader('Authorization', 'Bearer '.$usuario->createToken('student')->plainTextToken)->postJson($url, ['id_ingreso' => $this->ingreso->id_ingreso, 'motivo' => 'INTENTO_COPIA'])->assertForbidden();
    }
}
