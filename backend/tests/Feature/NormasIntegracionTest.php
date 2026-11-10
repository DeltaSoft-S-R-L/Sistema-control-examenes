<?php

namespace Tests\Feature;

use App\Models\Asignatura;
use App\Models\AuditLog;
use App\Models\Auditoria;
use App\Models\Carrera;
use App\Models\Estudiante;
use App\Models\Examen;
use App\Models\Facultad;
use App\Models\Habilitacion;
use App\Models\NormaExamen;
use App\Models\ReglaExamen;
use App\Models\ReglaIndividual;
use App\Models\Rol;
use App\Models\Usuario;
use App\Utilities\PasswordHasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NormasIntegracionTest extends TestCase
{
    use RefreshDatabase;

    private Rol $rolAdmin;
    private Rol $rolDocente;
    private Rol $rolControl;
    private Rol $rolEstudiante;

    private Usuario $userAdmin;
    private Usuario $userDocente;
    private Usuario $userControl;
    private Usuario $userEstudiante;

    private string $tokenAdmin;
    private string $tokenDocente;
    private string $tokenControl;
    private string $tokenEstudiante;

    private Examen $examen;
    private Estudiante $estudiante;
    private Habilitacion $habilitacion;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rolAdmin = Rol::create(['nombre' => 'ADMINISTRADOR', 'descripcion' => 'Admin']);
        $this->rolDocente = Rol::create(['nombre' => 'DOCENTE', 'descripcion' => 'Docente']);
        $this->rolControl = Rol::create(['nombre' => 'CONTROL_INGRESO', 'descripcion' => 'Control']);
        $this->rolEstudiante = Rol::create(['nombre' => 'ESTUDIANTE', 'descripcion' => 'Estudiante']);

        $this->userAdmin = Usuario::create([
            'nombre'        => 'Admin',
            'apellido'      => 'Sistema',
            'correo'        => 'admin@normas.local',
            'username'      => 'admin_test',
            'password_hash' => PasswordHasher::hash('Password123'),
            'id_rol'        => $this->rolAdmin->id_rol,
            'estado'        => 'ACTIVO',
        ]);
        $this->tokenAdmin = $this->userAdmin->createToken('admin-token')->plainTextToken;

        $this->userDocente = Usuario::create([
            'nombre'        => 'Docente',
            'apellido'      => 'Prueba',
            'correo'        => 'docente@normas.local',
            'username'      => 'docente_test',
            'password_hash' => PasswordHasher::hash('Password123'),
            'id_rol'        => $this->rolDocente->id_rol,
            'estado'        => 'ACTIVO',
        ]);
        $this->tokenDocente = $this->userDocente->createToken('docente-token')->plainTextToken;

        $this->userControl = Usuario::create([
            'nombre'        => 'Control',
            'apellido'      => 'Prueba',
            'correo'        => 'control@normas.local',
            'username'      => 'control_test',
            'password_hash' => PasswordHasher::hash('Password123'),
            'id_rol'        => $this->rolControl->id_rol,
            'estado'        => 'ACTIVO',
        ]);
        $this->tokenControl = $this->userControl->createToken('control-token')->plainTextToken;

        $this->userEstudiante = Usuario::create([
            'nombre'        => 'Estudiante',
            'apellido'      => 'Prueba',
            'correo'        => 'estudiante@normas.local',
            'username'      => 'estudiante_test',
            'password_hash' => PasswordHasher::hash('Password123'),
            'id_rol'        => $this->rolEstudiante->id_rol,
            'estado'        => 'ACTIVO',
        ]);
        $this->tokenEstudiante = $this->userEstudiante->createToken('estudiante-token')->plainTextToken;

        $facultad = Facultad::create(['codigo' => 'FCYT', 'nombre' => 'Facultad de Ciencias y Tecnologia']);
        $carrera = Carrera::create([
            'id_facultad' => $facultad->id_facultad,
            'codigo'      => 'SIS',
            'nombre'      => 'Ingenieria de Sistemas',
        ]);
        $asignatura = Asignatura::create(['codigo' => 'SIS-101', 'nombre' => 'Programacion I']);

        $this->examen = Examen::create([
            'id_asignatura'    => $asignatura->id_asignatura,
            'nombre'           => 'Examen Final de Sistemas',
            'fecha'            => '2026-11-20',
            'hora_inicio'      => '08:00',
            'duracion_minutos' => 90,
            'estado'           => 'PROGRAMADO',
        ]);

        $this->estudiante = Estudiante::create([
            'ci'                   => '7654321',
            'nombre'               => 'Ana',
            'apellido'             => 'Torres',
            'codigo_universitario' => '2023001',
            'id_carrera'           => $carrera->id_carrera,
            'correo'               => 'ana.torres@est.umss.edu',
            'estado'               => 'ACTIVO',
        ]);

        $this->habilitacion = Habilitacion::create([
            'id_examen'     => $this->examen->id_examen,
            'id_estudiante' => $this->estudiante->id_estudiante,
            'estado'        => 'HABILITADO',
        ]);
    }

    private function headers(string $token): array
    {
        app('auth')->forgetGuards();

        return [
            'Authorization' => "Bearer {$token}",
            'Accept'        => 'application/json',
        ];
    }

    public function test_compatibilidad_contrato_json_con_frontend_actual(): void
    {
        $payloadParticular = [
            'tipo'          => 'PARTICULAR',
            'descripcion'   => 'Permiso especial para rendir examen con computadora.',
            'id_estudiante' => $this->estudiante->id_estudiante,
        ];

        $resPost = $this->postJson(
            "/api/examenes/{$this->examen->id_examen}/normas",
            $payloadParticular,
            $this->headers($this->tokenDocente)
        );

        $resPost->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'norma' => [
                    'id_norma',
                    'tipo',
                    'descripcion',
                    'estado',
                    'estudiante' => [
                        'id_estudiante',
                        'nombre',
                        'apellido',
                        'nombre_completo',
                        'ci',
                        'codigo_universitario',
                    ],
                ],
                'id',
                'id_norma',
                'tipo',
                'descripcion',
            ]);

        $this->assertEquals('Norma particular registrada correctamente.', $resPost->json('message'));
        $this->assertEquals('Ana', $resPost->json('norma.estudiante.nombre'));
        $this->assertEquals('Torres', $resPost->json('norma.estudiante.apellido'));
        $this->assertEquals('7654321', $resPost->json('norma.estudiante.ci'));
        $this->assertEquals('2023001', $resPost->json('norma.estudiante.codigo_universitario'));

        $resGet = $this->getJson(
            "/api/examenes/{$this->examen->id_examen}/normas",
            $this->headers($this->tokenControl)
        );

        $resGet->assertStatus(200);
        $items = $resGet->json('data') ?? $resGet->json();
        $this->assertNotEmpty($items);

        $item = $items[0];
        $this->assertArrayHasKey('id_norma', $item);
        $this->assertArrayHasKey('id', $item);
        $this->assertEquals('PARTICULAR', $item['tipo']);
        $this->assertArrayHasKey('estudiante', $item);
        $this->assertEquals('Ana', $item['estudiante']['nombre']);
        $this->assertEquals('Torres', $item['estudiante']['apellido']);
        $this->assertEquals('7654321', $item['estudiante']['ci']);
        $this->assertEquals('2023001', $item['estudiante']['codigo_universitario']);
    }

    public function test_lectura_y_conservacion_de_normas_existentes_en_tablas_legadas(): void
    {
        $reglaG = ReglaExamen::create([
            'id_examen'   => $this->examen->id_examen,
            'descripcion' => 'Norma general legada existente',
            'estado'      => 'ACTIVA',
        ]);

        $reglaP = ReglaIndividual::create([
            'id_habilitacion' => $this->habilitacion->id_habilitacion,
            'descripcion'     => 'Norma particular legada existente',
            'estado'          => 'ACTIVA',
        ]);

        $response = $this->getJson(
            "/api/examenes/{$this->examen->id_examen}/normas",
            $this->headers($this->tokenDocente)
        );

        $response->assertStatus(200);
        $data = $response->json('data') ?? $response->json();
        $this->assertCount(2, $data);

        $descripciones = array_column($data, 'descripcion');
        $this->assertContains('Norma general legada existente', $descripciones);
        $this->assertContains('Norma particular legada existente', $descripciones);

        $resDelete = $this->deleteJson(
            "/api/examenes/{$this->examen->id_examen}/normas/{$reglaG->id_regla_examen}",
            [],
            $this->headers($this->tokenAdmin)
        );

        $this->assertTrue(in_array($resDelete->status(), [200, 204], true));

        $this->assertDatabaseMissing('regla_examen', [
            'id_regla_examen' => $reglaG->id_regla_examen,
        ]);
    }

    public function test_comportamiento_de_auditoria_integrada_en_ambas_bitacoras(): void
    {
        $conteoAuditLogsPrevio = AuditLog::count();
        $conteoAuditoriaPrevio = Auditoria::count();

        $resPost = $this->postJson(
            "/api/examenes/{$this->examen->id_examen}/normas",
            [
                'tipo'        => 'GENERAL',
                'descripcion' => 'Prohibido el uso de telefonos celulares.',
            ],
            $this->headers($this->tokenDocente)
        );

        $resPost->assertStatus(201);
        $normaId = $resPost->json('norma.id_norma') ?? $resPost->json('id');

        $this->assertEquals($conteoAuditLogsPrevio + 1, AuditLog::count());
        $this->assertEquals($conteoAuditoriaPrevio + 1, Auditoria::count());

        $logAuditLog = AuditLog::latest('id_audit_log')->first();
        $this->assertEquals('NORMA_CREADA', $logAuditLog->accion);
        $this->assertEquals('norma_examen', $logAuditLog->entidad);
        $this->assertEquals($this->userDocente->id_usuario, $logAuditLog->id_usuario);

        $logAuditoria = Auditoria::latest('id_auditoria')->first();
        $this->assertEquals('CREACION_NORMA', $logAuditoria->accion);
        $this->assertEquals('EXAMEN', $logAuditoria->entidad);
        $this->assertEquals($this->userDocente->id_usuario, $logAuditoria->id_usuario);

        $resDelete = $this->deleteJson(
            "/api/examenes/{$this->examen->id_examen}/normas/{$normaId}",
            [],
            $this->headers($this->tokenDocente)
        );

        $this->assertTrue(in_array($resDelete->status(), [200, 204], true));

        $this->assertEquals($conteoAuditLogsPrevio + 2, AuditLog::count());
        $this->assertEquals($conteoAuditoriaPrevio + 2, Auditoria::count());

        $logAuditLogDel = AuditLog::latest('id_audit_log')->first();
        $this->assertEquals('NORMA_ELIMINADA', $logAuditLogDel->accion);

        $logAuditoriaDel = Auditoria::latest('id_auditoria')->first();
        $this->assertEquals('ELIMINACION_NORMA', $logAuditoriaDel->accion);
    }

    public function test_permisos_de_acceso_estricto_por_rol(): void
    {
        $resCrearControl = $this->postJson(
            "/api/examenes/{$this->examen->id_examen}/normas",
            [
                'tipo'        => 'GENERAL',
                'descripcion' => 'Intento de creacion por control',
            ],
            $this->headers($this->tokenControl)
        );
        $resCrearControl->assertStatus(403);

        $resCrearEstudiante = $this->postJson(
            "/api/examenes/{$this->examen->id_examen}/normas",
            [
                'tipo'        => 'GENERAL',
                'descripcion' => 'Intento de creacion por estudiante',
            ],
            $this->headers($this->tokenEstudiante)
        );
        $resCrearEstudiante->assertStatus(403);

        $resListarControl = $this->getJson(
            "/api/examenes/{$this->examen->id_examen}/normas",
            $this->headers($this->tokenControl)
        );
        $resListarControl->assertStatus(200);

        $resListarEstudiante = $this->getJson(
            "/api/examenes/{$this->examen->id_examen}/normas",
            $this->headers($this->tokenEstudiante)
        );
        $resListarEstudiante->assertStatus(403);

        app('auth')->forgetGuards();

        $resSinAuth = $this->getJson(
            "/api/examenes/{$this->examen->id_examen}/normas",
            ['Accept' => 'application/json']
        );
        $resSinAuth->assertStatus(401);
    }

    public function test_manejo_de_errores_de_validacion_para_formulario_frontend(): void
    {
        $resSinEstudiante = $this->postJson(
            "/api/examenes/{$this->examen->id_examen}/normas",
            [
                'tipo'        => 'PARTICULAR',
                'descripcion' => 'Calculadora autorizada',
            ],
            $this->headers($this->tokenDocente)
        );

        $resSinEstudiante->assertStatus(422)
            ->assertJsonValidationErrors(['estudiante_id'])
            ->assertJsonValidationErrors(['id_estudiante']);
    }
}
