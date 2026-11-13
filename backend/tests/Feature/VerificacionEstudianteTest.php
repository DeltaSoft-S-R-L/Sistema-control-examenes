<?php

namespace Tests\Feature;

use App\Enums\AccionAuditoria;
use App\Enums\CodigoErrorVerificacion;
use App\Enums\MotivoIntentoIngreso;
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
use App\Services\HabilitacionService;
use App\Services\QrTokenService;
use App\Utilities\PasswordHasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class VerificacionEstudianteTest extends TestCase
{
    use RefreshDatabase;

    private Rol $rolControl;
    private Rol $rolDocente;
    private Usuario $userControl;
    private Usuario $userDocente;
    private string $tokenControl;
    private string $tokenDocente;

    private Examen $examen;
    private AsignacionAmbiente $asignacionAmbiente;
    private Habilitacion $habilitacionHabilitado;
    private Habilitacion $habilitacionInhabilitado;
    private Estudiante $estudianteHabilitado;
    private Estudiante $estudianteInhabilitado;
    private Estudiante $estudianteNoAsociado;
    private QrTokenService $qrService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->qrService = app(QrTokenService::class);

        // 1. Roles
        $this->rolControl = Rol::create([
            'nombre'      => 'CONTROL_INGRESO',
            'descripcion' => 'Personal de control de ingreso',
        ]);

        $this->rolDocente = Rol::create([
            'nombre'      => 'DOCENTE',
            'descripcion' => 'Docente universitario',
        ]);

        // 2. Usuarios
        $this->userControl = Usuario::create([
            'nombre'        => 'Carlos',
            'apellido'      => 'Control',
            'correo'        => 'carlos.control@universidad.edu',
            'username'      => 'control1',
            'password_hash' => PasswordHasher::hash('Password123'),
            'id_rol'        => $this->rolControl->id_rol,
            'estado'        => 'ACTIVO',
        ]);
        $this->tokenControl = $this->userControl->createToken('token-control')->plainTextToken;

        $this->userDocente = Usuario::create([
            'nombre'        => 'Laura',
            'apellido'      => 'Docente',
            'correo'        => 'laura.docente@universidad.edu',
            'username'      => 'docente1',
            'password_hash' => PasswordHasher::hash('Password123'),
            'id_rol'        => $this->rolDocente->id_rol,
            'estado'        => 'ACTIVO',
        ]);
        $this->tokenDocente = $this->userDocente->createToken('token-docente')->plainTextToken;

        // 3. Estructura académica
        $facultad = Facultad::create([
            'codigo' => 'FCYT',
            'nombre' => 'Facultad de Ciencias y Tecnología',
        ]);

        $carrera = Carrera::create([
            'id_facultad' => $facultad->id_facultad,
            'codigo'      => '411702',
            'nombre'      => 'Ingeniería de Sistemas',
        ]);

        $asignatura = Asignatura::create([
            'codigo'      => 'SIS-101',
            'nombre'      => 'Ingeniería de Software',
            'descripcion' => 'Materia de prueba',
        ]);

        // 4. Examen y Ambiente
        $this->examen = Examen::create([
            'id_asignatura'    => $asignatura->id_asignatura,
            'nombre'           => 'Examen Parcial de Prueba',
            'fecha'            => '2026-10-27',
            'hora_inicio'      => '08:00',
            'duracion_minutos' => 90,
            'estado'           => 'PROGRAMADO',
        ]);

        $ambiente = Ambiente::create([
            'codigo'    => '691A',
            'nombre'    => 'Aula 691A',
            'capacidad' => 50,
            'ubicacion' => 'Edificio Nuevo',
            'estado'    => 'DISPONIBLE',
        ]);

        $this->asignacionAmbiente = AsignacionAmbiente::create([
            'id_examen'   => $this->examen->id_examen,
            'id_ambiente' => $ambiente->id_ambiente,
        ]);

        // 5. Estudiantes
        $this->estudianteHabilitado = Estudiante::create([
            'ci'                   => '1111111',
            'nombre'               => 'Juan',
            'apellido'             => 'Pérez',
            'codigo_universitario' => '202000001',
            'id_carrera'           => $carrera->id_carrera,
            'correo'               => 'juan.perez@universidad.edu',
            'estado'               => 'ACTIVO',
        ]);

        $this->estudianteInhabilitado = Estudiante::create([
            'ci'                   => '2222222',
            'nombre'               => 'María',
            'apellido'             => 'López',
            'codigo_universitario' => '202000002',
            'id_carrera'           => $carrera->id_carrera,
            'correo'               => 'maria.lopez@universidad.edu',
            'estado'               => 'ACTIVO',
        ]);

        $this->estudianteNoAsociado = Estudiante::create([
            'ci'                   => '3333333',
            'nombre'               => 'Pedro',
            'apellido'             => 'Gómez',
            'codigo_universitario' => '202000003',
            'id_carrera'           => $carrera->id_carrera,
            'correo'               => 'pedro.gomez@universidad.edu',
            'estado'               => 'ACTIVO',
        ]);

        // 6. Habilitaciones
        $this->habilitacionHabilitado = Habilitacion::create([
            'id_estudiante'          => $this->estudianteHabilitado->id_estudiante,
            'id_examen'              => $this->examen->id_examen,
            'id_asignacion_ambiente' => $this->asignacionAmbiente->id_asignacion_ambiente,
            'estado'                 => 'HABILITADO',
            'motivo'                 => null,
        ]);

        $this->habilitacionInhabilitado = Habilitacion::create([
            'id_estudiante'          => $this->estudianteInhabilitado->id_estudiante,
            'id_examen'              => $this->examen->id_examen,
            'id_asignacion_ambiente' => $this->asignacionAmbiente->id_asignacion_ambiente,
            'estado'                 => 'NO_HABILITADO',
            'motivo'                 => 'No cumple requisito de asistencia mínima (65%)',
        ]);
        // $this->estudianteNoAsociado no tiene registro en Habilitacion
    }

    // ─────────────────────────────────────────────────────────────────
    // 1. Estudiante Habilitado
    // ─────────────────────────────────────────────────────────────────
    public function test_estudiante_habilitado_retorna_identificacion_exitosa(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->tokenControl}")
            ->postJson('/api/verificaciones/documento', [
                'tipo'      => 'CI',
                'valor'     => $this->estudianteHabilitado->ci,
                'examen_id' => $this->examen->id_examen,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'metodo'     => 'CI',
                'estudiante' => [
                    'ci' => '1111111',
                ],
                'examen'     => [
                    'id' => $this->examen->id_examen,
                ],
            ]);
    }

    public function test_ingreso_previo_retorna_reingreso_con_fecha_y_ambiente(): void
    {
        $fechaHora = Carbon::parse('2026-10-27 08:15:00');
        $ambiente = $this->registrarIngresoPrevio($this->estudianteHabilitado, $fechaHora);

        // 1. Verificación de identidad con contrato unificado VER-01
        $response = $this->withHeader('Authorization', "Bearer {$this->tokenControl}")
            ->postJson('/api/verificaciones/documento', [
                'tipo'      => 'CI',
                'valor'     => $this->estudianteHabilitado->ci,
                'examen_id' => $this->examen->id_examen,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'metodo'     => 'CI',
                'estudiante' => [
                    'ci' => $this->estudianteHabilitado->ci,
                ],
                'examen'     => [
                    'id' => $this->examen->id_examen,
                ],
            ]);

        // 2. Consulta de habilitación reporta REINGRESO con fecha y ambiente
        $resultado = HabilitacionService::verificarHabilitacion($this->estudianteHabilitado, $this->examen);

        $this->assertEquals('REINGRESO', $resultado['estado']);
        $this->assertFalse($resultado['habilitado']);
        $this->assertEquals($ambiente->nombre, $resultado['ingreso_previo']['ambiente']);

        $fechaHoraRespuesta = Carbon::parse($resultado['ingreso_previo']['fecha_hora']);
        $this->assertTrue($fechaHoraRespuesta->equalTo($fechaHora));
    }

    public function test_ingreso_previo_tiene_prioridad_sobre_habilitacion_no_habilitado(): void
    {
        $fechaHora = Carbon::parse('2026-10-27 08:15:00');
        $ambiente = $this->registrarIngresoPrevio($this->estudianteInhabilitado, $fechaHora);

        // 1. Identificación exitosa por documento
        $response = $this->withHeader('Authorization', "Bearer {$this->tokenControl}")
            ->postJson('/api/verificaciones/documento', [
                'tipo'      => 'CI',
                'valor'     => $this->estudianteInhabilitado->ci,
                'examen_id' => $this->examen->id_examen,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'metodo'     => 'CI',
                'estudiante' => [
                    'ci' => $this->estudianteInhabilitado->ci,
                ],
            ]);

        // 2. Prioridad de REINGRESO sobre NO_HABILITADO
        $resultado = HabilitacionService::verificarHabilitacion($this->estudianteInhabilitado, $this->examen);

        $this->assertEquals('REINGRESO', $resultado['estado']);
        $this->assertFalse($resultado['habilitado']);
        $this->assertEquals($ambiente->nombre, $resultado['ingreso_previo']['ambiente']);

        $fechaHoraRespuesta = Carbon::parse($resultado['ingreso_previo']['fecha_hora']);
        $this->assertTrue($fechaHoraRespuesta->equalTo($fechaHora));
    }

    private function registrarIngresoPrevio(Estudiante $estudiante, Carbon $fechaHora): Ambiente
    {
        $ambiente = Ambiente::create([
            'codigo'    => 'SALA-REINGRESO',
            'nombre'    => 'Sala para prueba de reingreso',
            'ubicacion' => 'Bloque A - Piso 1',
            'capacidad' => 30,
            'estado'    => 'DISPONIBLE',
        ]);

        $asignacion = AsignacionAmbiente::create([
            'id_examen'   => $this->examen->id_examen,
            'id_ambiente' => $ambiente->id_ambiente,
        ]);

        $habilitacion = Habilitacion::where('id_estudiante', $estudiante->id_estudiante)
            ->where('id_examen', $this->examen->id_examen)
            ->firstOrFail();

        Ingreso::create([
            'id_examen'                => $this->examen->id_examen,
            'id_habilitacion'          => $habilitacion->id_habilitacion,
            'id_asignacion_ambiente'   => $asignacion->id_asignacion_ambiente,
            'id_usuario'               => $this->userControl->id_usuario,
            'fecha_hora'               => $fechaHora,
        ]);

        return $ambiente;
    }

    // ─────────────────────────────────────────────────────────────────
    // 2. Estudiante Inhabilitado con Motivo
    // ─────────────────────────────────────────────────────────────────
    public function test_estudiante_inhabilitado_bloqueado_en_ambiente_y_expone_motivo(): void
    {
        // Identidad por SIS exitosa
        $resIdentidad = $this->withHeader('Authorization', "Bearer {$this->tokenControl}")
            ->postJson('/api/verificaciones/documento', [
                'tipo'      => 'SIS',
                'valor'     => $this->estudianteInhabilitado->codigo_universitario,
                'examen_id' => $this->examen->id_examen,
            ]);

        $resIdentidad->assertStatus(200)
            ->assertJson([
                'metodo'     => 'SIS',
                'estudiante' => [
                    'codigo_sis' => '202000002',
                ],
            ]);

        // Al verificar ambiente o intentar ingreso, retorna 403 NO_HABILITADO
        $resAmbiente = $this->withHeader('Authorization', "Bearer {$this->tokenControl}")
            ->postJson('/api/verificaciones/ambiente', [
                'id_examen'              => $this->examen->id_examen,
                'id_asignacion_ambiente' => $this->asignacionAmbiente->id_asignacion_ambiente,
                'id_habilitacion'        => $this->habilitacionInhabilitado->id_habilitacion,
            ]);

        $resAmbiente->assertStatus(403)
            ->assertJson([
                'code'  => CodigoErrorVerificacion::NO_HABILITADO->value,
                'error' => CodigoErrorVerificacion::NO_HABILITADO->value,
            ]);

        // Consulta del motivo de inhabilitación
        $resMotivo = $this->withHeader('Authorization', "Bearer {$this->tokenControl}")
            ->getJson("/api/examenes/{$this->examen->id_examen}/estudiantes/{$this->estudianteInhabilitado->id_estudiante}/inhabilitacion");

        $resMotivo->assertStatus(200)
            ->assertJson([
                'estado'         => 'NO_HABILITADO',
                'habilitado'     => false,
                'motivo_detalle' => 'No cumple requisito de asistencia mínima (65%)',
                'motivo'         => [
                    'detalle' => 'No cumple requisito de asistencia mínima (65%)',
                ],
            ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // 3. Estudiante No Asociado
    // ─────────────────────────────────────────────────────────────────
    public function test_estudiante_no_asociado_retorna_422_y_registra_intento_indebido(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->tokenControl}")
            ->postJson('/api/verificaciones/documento', [
                'tipo'      => 'CI',
                'valor'     => $this->estudianteNoAsociado->ci,
                'examen_id' => $this->examen->id_examen,
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'code'  => CodigoErrorVerificacion::NO_ASOCIADO->value,
                'error' => CodigoErrorVerificacion::NO_ASOCIADO->value,
            ]);

        $this->assertDatabaseHas('intento_ingreso', [
            'id_estudiante' => $this->estudianteNoAsociado->id_estudiante,
            'id_examen'     => $this->examen->id_examen,
            'motivo'        => MotivoIntentoIngreso::EXAMEN_NO_CORRESPONDIENTE->value,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // 4. Comprobar que QR y CI/SIS retornan datos consistentes del mismo estudiante y examen
    // ─────────────────────────────────────────────────────────────────
    public function test_qr_y_ci_sis_dan_el_mismo_resultado_para_el_mismo_estudiante(): void
    {
        $qrToken = $this->qrService->generar($this->estudianteHabilitado);

        $resQr = $this->withHeader('Authorization', "Bearer {$this->tokenControl}")
            ->postJson('/api/verificaciones/qr', [
                'token'     => $qrToken,
                'examen_id' => $this->examen->id_examen,
            ]);

        $resCi = $this->withHeader('Authorization', "Bearer {$this->tokenControl}")
            ->postJson('/api/verificaciones/documento', [
                'tipo'      => 'CI',
                'valor'     => $this->estudianteHabilitado->ci,
                'examen_id' => $this->examen->id_examen,
            ]);

        $resSis = $this->withHeader('Authorization', "Bearer {$this->tokenControl}")
            ->postJson('/api/verificaciones/documento', [
                'tipo'      => 'SIS',
                'valor'     => $this->estudianteHabilitado->codigo_universitario,
                'examen_id' => $this->examen->id_examen,
            ]);

        $resQr->assertStatus(200);
        $resCi->assertStatus(200);
        $resSis->assertStatus(200);

        $jsonQr = $resQr->json();
        $jsonCi = $resCi->json();
        $jsonSis = $resSis->json();

        $this->assertEquals($jsonQr['estudiante'], $jsonCi['estudiante']);
        $this->assertEquals($jsonCi['estudiante'], $jsonSis['estudiante']);
        $this->assertEquals($jsonQr['examen'], $jsonCi['examen']);
        $this->assertEquals($jsonCi['examen'], $jsonSis['examen']);
    }

    // ─────────────────────────────────────────────────────────────────
    // 5. Inhabilitar a un estudiante habilitado cambia el resultado en control de puerta
    // ─────────────────────────────────────────────────────────────────
    public function test_inhabilitar_estudiante_habilitado_cambia_resultado_en_siguiente_verificacion(): void
    {
        // 1ª verificación de ambiente: Permitido (200)
        $res1 = $this->withHeader('Authorization', "Bearer {$this->tokenControl}")
            ->postJson('/api/verificaciones/ambiente', [
                'id_examen'              => $this->examen->id_examen,
                'id_asignacion_ambiente' => $this->asignacionAmbiente->id_asignacion_ambiente,
                'id_habilitacion'        => $this->habilitacionHabilitado->id_habilitacion,
            ]);

        $res1->assertStatus(200)->assertJson(['valido' => true]);

        // Modificamos habilitación en BD a NO_HABILITADO con motivo
        $this->habilitacionHabilitado->update([
            'estado' => 'NO_HABILITADO',
            'motivo' => 'Inhabilitado por sanción disciplinaria',
        ]);

        // 2ª verificación de ambiente: Cambia a 403 NO_HABILITADO
        $res2 = $this->withHeader('Authorization', "Bearer {$this->tokenControl}")
            ->postJson('/api/verificaciones/ambiente', [
                'id_examen'              => $this->examen->id_examen,
                'id_asignacion_ambiente' => $this->asignacionAmbiente->id_asignacion_ambiente,
                'id_habilitacion'        => $this->habilitacionHabilitado->id_habilitacion,
            ]);

        $res2->assertStatus(403)
            ->assertJson([
                'code'  => CodigoErrorVerificacion::NO_HABILITADO->value,
                'error' => CodigoErrorVerificacion::NO_HABILITADO->value,
            ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // 6. Verificar que NO se escribe ninguna fila en tablas de dominio (solo audit_logs)
    // ─────────────────────────────────────────────────────────────────
    public function test_verificacion_no_escribe_en_tablas_de_dominio_solo_en_auditoria(): void
    {
        $totalIngresosPrevio = Ingreso::count();
        $totalHabilitacionesPrevio = Habilitacion::count();
        $totalEstudiantesPrevio = Estudiante::count();
        $totalExamenesPrevio = Examen::count();
        $totalAuditLogsPrevio = AuditLog::where('accion', AccionAuditoria::VERIFICACION_IDENTIDAD->value)->count();

        $qrToken = $this->qrService->generar($this->estudianteHabilitado);

        $response = $this->withHeader('Authorization', "Bearer {$this->tokenControl}")
            ->postJson('/api/verificaciones/qr', [
                'token'     => $qrToken,
                'examen_id' => $this->examen->id_examen,
            ]);

        $response->assertStatus(200);

        $this->assertEquals($totalIngresosPrevio, Ingreso::count(), 'No debe registrarse fila en ingreso.');
        $this->assertEquals($totalHabilitacionesPrevio, Habilitacion::count(), 'No debe alterarse habilitacion.');
        $this->assertEquals($totalEstudiantesPrevio, Estudiante::count(), 'No debe alterarse estudiante.');
        $this->assertEquals($totalExamenesPrevio, Examen::count(), 'No debe alterarse examen.');

        $this->assertEquals(
            $totalAuditLogsPrevio + 1,
            AuditLog::where('accion', AccionAuditoria::VERIFICACION_IDENTIDAD->value)->count(),
            'Debe crearse exactamente un registro en audit_logs.'
        );
    }

    // ─────────────────────────────────────────────────────────────────
    // 7. QR válido vs QR alterado vs Token con otro formato
    // ─────────────────────────────────────────────────────────────────
    public function test_verificacion_qr_valido_alterado_y_otro_formato(): void
    {
        $tokenValido = $this->qrService->generar($this->estudianteHabilitado);

        // QR Válido -> 200
        $resValido = $this->withHeader('Authorization', "Bearer {$this->tokenControl}")
            ->postJson('/api/verificaciones/qr', [
                'token'     => $tokenValido,
                'examen_id' => $this->examen->id_examen,
            ]);
        $resValido->assertStatus(200);

        // QR Alterado (modificamos firma) -> 422 QR_INVALIDO
        $tokenAlterado = substr($tokenValido, 0, -3) . 'xyz';
        $resAlterado = $this->withHeader('Authorization', "Bearer {$this->tokenControl}")
            ->postJson('/api/verificaciones/qr', [
                'token'     => $tokenAlterado,
                'examen_id' => $this->examen->id_examen,
            ]);
        $resAlterado->assertStatus(422)
            ->assertJson([
                'code'  => CodigoErrorVerificacion::QR_INVALIDO->value,
                'error' => CodigoErrorVerificacion::QR_INVALIDO->value,
            ]);

        // Token con otro formato (sin punto o corrupto) -> 422 QR_INVALIDO
        $resOtroFormato = $this->withHeader('Authorization', "Bearer {$this->tokenControl}")
            ->postJson('/api/verificaciones/qr', [
                'token'     => 'cadena-sin-formato-qr',
                'examen_id' => $this->examen->id_examen,
            ]);
        $resOtroFormato->assertStatus(422)
            ->assertJson([
                'code'  => CodigoErrorVerificacion::QR_INVALIDO->value,
                'error' => CodigoErrorVerificacion::QR_INVALIDO->value,
            ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // 8. CI existente vs CI inexistente
    // ─────────────────────────────────────────────────────────────────
    public function test_verificacion_ci_existente_e_inexistente(): void
    {
        $resExistente = $this->withHeader('Authorization', "Bearer {$this->tokenControl}")
            ->postJson('/api/verificaciones/documento', [
                'tipo'      => 'CI',
                'valor'     => $this->estudianteHabilitado->ci,
                'examen_id' => $this->examen->id_examen,
            ]);
        $resExistente->assertStatus(200)->assertJson(['metodo' => 'CI']);

        $resInexistente = $this->withHeader('Authorization', "Bearer {$this->tokenControl}")
            ->postJson('/api/verificaciones/documento', [
                'tipo'      => 'CI',
                'valor'     => '9999999999',
                'examen_id' => $this->examen->id_examen,
            ]);
        $resInexistente->assertStatus(404)
            ->assertJson([
                'code'  => CodigoErrorVerificacion::ESTUDIANTE_NO_ENCONTRADO->value,
                'error' => CodigoErrorVerificacion::ESTUDIANTE_NO_ENCONTRADO->value,
            ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // 9. SIS existente vs SIS inexistente
    // ─────────────────────────────────────────────────────────────────
    public function test_verificacion_sis_existente_e_inexistente(): void
    {
        $resExistente = $this->withHeader('Authorization', "Bearer {$this->tokenControl}")
            ->postJson('/api/verificaciones/documento', [
                'tipo'      => 'SIS',
                'valor'     => $this->estudianteInhabilitado->codigo_universitario,
                'examen_id' => $this->examen->id_examen,
            ]);
        $resExistente->assertStatus(200)->assertJson(['metodo' => 'SIS']);

        $resInexistente = $this->withHeader('Authorization', "Bearer {$this->tokenControl}")
            ->postJson('/api/verificaciones/documento', [
                'tipo'      => 'SIS',
                'valor'     => 'SIS_NO_EXISTE_000',
                'examen_id' => $this->examen->id_examen,
            ]);
        $resInexistente->assertStatus(404)
            ->assertJson([
                'code'  => CodigoErrorVerificacion::ESTUDIANTE_NO_ENCONTRADO->value,
                'error' => CodigoErrorVerificacion::ESTUDIANTE_NO_ENCONTRADO->value,
            ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // 10. Valores con espacios en blanco alrededor
    // ─────────────────────────────────────────────────────────────────
    public function test_verificacion_con_espacios_en_blanco(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->tokenControl}")
            ->postJson('/api/verificaciones/documento', [
                'tipo'      => 'CI',
                'valor'     => "   {$this->estudianteHabilitado->ci}   ",
                'examen_id' => $this->examen->id_examen,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'metodo'     => 'CI',
                'estudiante' => [
                    'ci' => $this->estudianteHabilitado->ci,
                ],
            ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // 11. Sin sesión (401)
    // ─────────────────────────────────────────────────────────────────
    public function test_verificacion_sin_sesion_retorna_401(): void
    {
        $response = $this->postJson('/api/verificaciones/documento', [
            'tipo'      => 'CI',
            'valor'     => $this->estudianteHabilitado->ci,
            'examen_id' => $this->examen->id_examen,
        ]);

        $response->assertStatus(401);
    }

    // ─────────────────────────────────────────────────────────────────
    // 12. Rol Docente (403)
    // ─────────────────────────────────────────────────────────────────
    public function test_verificacion_con_rol_docente_retorna_403(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->tokenDocente}")
            ->postJson('/api/verificaciones/documento', [
                'tipo'      => 'CI',
                'valor'     => $this->estudianteHabilitado->ci,
                'examen_id' => $this->examen->id_examen,
            ]);

        $response->assertStatus(403);
    }

    // ─────────────────────────────────────────────────────────────────
    // 13. Comprobar que cada verificación genera su registro de auditoría
    // ─────────────────────────────────────────────────────────────────
    public function test_cada_verificacion_genera_su_registro_de_auditoria(): void
    {
        AuditLog::query()->delete();

        // 1. Verificación exitosa por QR
        $qrToken = $this->qrService->generar($this->estudianteHabilitado);
        $this->withHeader('Authorization', "Bearer {$this->tokenControl}")
            ->postJson('/api/verificaciones/qr', [
                'token'     => $qrToken,
                'examen_id' => $this->examen->id_examen,
            ])->assertStatus(200);

        $auditoriaQr = AuditLog::where('accion', AccionAuditoria::VERIFICACION_IDENTIDAD->value)
            ->latest('id_audit_log')
            ->first();
        $this->assertNotNull($auditoriaQr);
        $this->assertEquals($this->userControl->id_usuario, $auditoriaQr->id_usuario);
        $this->assertEquals((string) $this->estudianteHabilitado->id_estudiante, $auditoriaQr->id_registro);
        $this->assertEquals('QR', $auditoriaQr->informacion['metodo'] ?? null);
        $this->assertEquals('EXITOSO', $auditoriaQr->informacion['resultado'] ?? null);

        // 2. Verificación de estudiante inexistente por CI
        $this->withHeader('Authorization', "Bearer {$this->tokenControl}")
            ->postJson('/api/verificaciones/documento', [
                'tipo'      => 'CI',
                'valor'     => 'CI_INEXISTENTE_999',
                'examen_id' => $this->examen->id_examen,
            ])->assertStatus(404);

        $auditoriaInexistente = AuditLog::where('accion', AccionAuditoria::VERIFICACION_IDENTIDAD->value)
            ->latest('id_audit_log')
            ->first();

        $this->assertNotNull($auditoriaInexistente);
        $this->assertNull($auditoriaInexistente->id_registro);
        $this->assertEquals(
            CodigoErrorVerificacion::ESTUDIANTE_NO_ENCONTRADO->value,
            $auditoriaInexistente->informacion['resultado'] ?? null
        );
    }

    // ─────────────────────────────────────────────────────────────────
    // 14. Verificar que las rutas legacy /api/verificar/* fueron eliminadas
    // ─────────────────────────────────────────────────────────────────
    public function test_rutas_legacy_verificar_retornan_404(): void
    {
        $this->withHeader('Authorization', "Bearer {$this->tokenControl}")
            ->postJson('/api/verificar/qr', ['token' => 'abc', 'id_examen' => $this->examen->id_examen])
            ->assertStatus(404);

        $this->withHeader('Authorization', "Bearer {$this->tokenControl}")
            ->postJson('/api/verificar/documento', ['identificador' => '1111111', 'id_examen' => $this->examen->id_examen])
            ->assertStatus(404);

        $this->withHeader('Authorization', "Bearer {$this->tokenControl}")
            ->postJson('/api/verificar/ci-sis', ['ci_sis' => '202000001', 'id_examen' => $this->examen->id_examen])
            ->assertStatus(404);
    }
}
