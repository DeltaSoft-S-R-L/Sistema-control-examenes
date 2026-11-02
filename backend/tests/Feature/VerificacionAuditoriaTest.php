<?php

namespace Tests\Feature;

use App\Models\Asignatura;
use App\Models\AuditLog;
use App\Models\Carrera;
use App\Models\Estudiante;
use App\Models\Examen;
use App\Models\Facultad;
use App\Models\Rol;
use App\Models\Usuario;
use App\Services\AuditService;
use App\Services\QrTokenService;
use App\Utilities\PasswordHasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class VerificacionAuditoriaTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $controlUser;
    private string $controlToken;
    private Estudiante $estudiante;
    private Examen $examen;
    private QrTokenService $qrService;
    private string $validQrToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->qrService = app(QrTokenService::class);

        // 1. Rol y Usuario Control
        $rolControl = Rol::create([
            'nombre'      => 'CONTROL_INGRESO',
            'descripcion' => 'Personal de control de ingreso',
        ]);

        $this->controlUser = Usuario::create([
            'nombre'        => 'Carlos',
            'apellido'      => 'Controlador',
            'correo'        => 'carlos.control@umss.edu.bo',
            'username'      => 'carlos_control',
            'password_hash' => PasswordHasher::hash('Control123'),
            'id_rol'        => $rolControl->id_rol,
            'estado'        => 'ACTIVO',
        ]);
        $this->controlToken = $this->controlUser->createToken('control-token')->plainTextToken;

        // 2. Estructura Académica
        $facultad = Facultad::create([
            'codigo' => 'FCYT',
            'nombre' => 'Facultad de Ciencias y Tecnología',
        ]);

        $carrera = Carrera::create([
            'codigo'      => 'SIS',
            'nombre'      => 'Ingeniería de Sistemas',
            'id_facultad' => $facultad->id_facultad,
        ]);

        $asignatura = Asignatura::create([
            'codigo'      => '2010020',
            'nombre'      => 'INGENIERIA DE SOFTWARE',
            'descripcion' => 'Nivel F - Sistemas',
        ]);

        // 3. Estudiante
        $this->estudiante = Estudiante::create([
            'ci'                   => '7192834',
            'nombre'               => 'Valeria',
            'apellido'             => 'Rojas Almaraz',
            'codigo_universitario' => '202100888',
            'id_carrera'           => $carrera->id_carrera,
            'correo'               => 'valeria.rojas@umss.edu.bo',
            'estado'               => 'ACTIVO',
        ]);

        // 4. Examen
        $this->examen = Examen::create([
            'id_asignatura'    => $asignatura->id_asignatura,
            'nombre'           => 'Primer Parcial - Ing. Software',
            'fecha'            => '2026-11-20',
            'hora_inicio'      => '08:00:00',
            'duracion_minutos' => 90,
            'descripcion'      => 'Evaluación presencial',
            'estado'           => 'PROGRAMADO',
        ]);

        // 5. Token QR válido
        $this->validQrToken = $this->qrService->generar($this->estudiante);
    }

    /**
     * 1a. Verificación exitosa por QR registra exactamente 1 log con todos los metadatos requeridos.
     */
    public function test_verificacion_exitosa_por_qr_registra_auditoria(): void
    {
        $conteoPrevio = AuditLog::count();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->controlToken}",
            'Accept'        => 'application/json',
        ])->postJson('/api/verificaciones/qr', [
            'token'     => $this->validQrToken,
            'examen_id' => $this->examen->id_examen,
        ]);

        $response->assertStatus(200);

        $this->assertSame($conteoPrevio + 1, AuditLog::count());

        $log = AuditLog::latest('id_audit_log')->first();
        $this->assertNotNull($log);
        $this->assertSame('VERIFICACION_IDENTIDAD', $log->accion);
        $this->assertSame($this->controlUser->id_usuario, $log->id_usuario);
        $this->assertSame('estudiante', $log->entidad);
        $this->assertSame((string) $this->estudiante->id_estudiante, $log->id_registro);

        $this->assertSame('QR', $log->informacion['metodo']);
        $this->assertSame($this->examen->id_examen, $log->informacion['examen_id']);
        $this->assertSame($this->estudiante->id_estudiante, $log->informacion['estudiante_id']);
        $this->assertSame('EXITOSO', $log->informacion['resultado']);
        $this->assertNotNull($log->fecha_hora);
    }

    /**
     * 1b. Verificación exitosa por CI y SIS registra auditoría con método respectivo.
     */
    public function test_verificacion_exitosa_por_documento_ci_y_sis_registra_auditoria(): void
    {
        // Verificación por CI
        $conteoPrevio = AuditLog::count();
        $responseCi = $this->withHeaders([
            'Authorization' => "Bearer {$this->controlToken}",
            'Accept'        => 'application/json',
        ])->postJson('/api/verificaciones/documento', [
            'tipo'      => 'CI',
            'valor'     => '7192834',
            'examen_id' => $this->examen->id_examen,
        ]);

        $responseCi->assertStatus(200);
        $this->assertSame($conteoPrevio + 1, AuditLog::count());

        $logCi = AuditLog::latest('id_audit_log')->first();
        $this->assertSame('CI', $logCi->informacion['metodo']);
        $this->assertSame($this->controlUser->id_usuario, $logCi->id_usuario);
        $this->assertSame($this->estudiante->id_estudiante, $logCi->informacion['estudiante_id']);
        $this->assertSame($this->examen->id_examen, $logCi->informacion['examen_id']);
        $this->assertSame('EXITOSO', $logCi->informacion['resultado']);

        // Verificación por SIS
        $responseSis = $this->withHeaders([
            'Authorization' => "Bearer {$this->controlToken}",
            'Accept'        => 'application/json',
        ])->postJson('/api/verificaciones/documento', [
            'tipo'      => 'SIS',
            'valor'     => '202100888',
            'examen_id' => $this->examen->id_examen,
        ]);

        $responseSis->assertStatus(200);
        $this->assertSame($conteoPrevio + 2, AuditLog::count());

        $logSis = AuditLog::latest('id_audit_log')->first();
        $this->assertSame('SIS', $logSis->informacion['metodo']);
        $this->assertSame($this->controlUser->id_usuario, $logSis->id_usuario);
        $this->assertSame($this->estudiante->id_estudiante, $logSis->informacion['estudiante_id']);
        $this->assertSame('EXITOSO', $logSis->informacion['resultado']);
    }

    /**
     * 2a. Verificación fallida por estudiante inexistente en CI enmascara el valor
     * mostrando únicamente los últimos 3 caracteres y NO expone el documento completo.
     */
    public function test_verificacion_fallida_ci_inexistente_enmascara_valor_en_auditoria(): void
    {
        $ciInexistente = '84920191B';

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->controlToken}",
            'Accept'        => 'application/json',
        ])->postJson('/api/verificaciones/documento', [
            'tipo'      => 'CI',
            'valor'     => $ciInexistente,
            'examen_id' => $this->examen->id_examen,
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'code' => 'ESTUDIANTE_NO_ENCONTRADO',
            ]);

        $log = AuditLog::latest('id_audit_log')->first();
        $this->assertNotNull($log);
        $this->assertSame('VERIFICACION_IDENTIDAD', $log->accion);
        $this->assertSame('ESTUDIANTE_NO_ENCONTRADO', $log->informacion['resultado']);
        $this->assertNull($log->informacion['estudiante_id']);

        // Comprobar que contiene únicamente los últimos 3 caracteres ("91B")
        $this->assertStringEndsWith('91B', $log->informacion['valor_enmascarado']);
        $this->assertSame('***91B', $log->informacion['valor_enmascarado']);

        // Comprobar que NUNCA expone el valor completo
        $this->assertStringNotContainsString($ciInexistente, json_encode($log->informacion));
    }

    /**
     * 2b. Verificación fallida por QR alterado enmascara el token en auditoría.
     */
    public function test_verificacion_fallida_qr_alterado_enmascara_token_en_auditoria(): void
    {
        [$payloadB64, $signatureB64] = explode('.', $this->validQrToken);
        $tamperedToken = $payloadB64 . '.' . substr($signatureB64, 0, -2) . 'ZZ';

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->controlToken}",
            'Accept'        => 'application/json',
        ])->postJson('/api/verificaciones/qr', [
            'token'     => $tamperedToken,
            'examen_id' => $this->examen->id_examen,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'code' => 'QR_INVALIDO',
            ]);

        $log = AuditLog::latest('id_audit_log')->first();
        $this->assertNotNull($log);
        $this->assertSame('QR_INVALIDO', $log->informacion['resultado']);
        $this->assertNull($log->informacion['estudiante_id']);

        // Token enmascarado termina con los últimos 3 caracteres pero no contiene el string completo
        $ultimos3 = substr($tamperedToken, -3);
        $this->assertStringEndsWith($ultimos3, $log->informacion['valor_enmascarado']);
        $this->assertStringNotContainsString($tamperedToken, json_encode($log->informacion));
    }

    /**
     * 2c. Verificación fallida por examen inexistente registra estudiante identificado.
     */
    public function test_verificacion_fallida_examen_inexistente_registra_estudiante_en_auditoria(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->controlToken}",
            'Accept'        => 'application/json',
        ])->postJson('/api/verificaciones/qr', [
            'token'     => $this->validQrToken,
            'examen_id' => 999999,
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'code' => 'EXAMEN_NO_ENCONTRADO',
            ]);

        $log = AuditLog::latest('id_audit_log')->first();
        $this->assertNotNull($log);
        $this->assertSame('EXAMEN_NO_ENCONTRADO', $log->informacion['resultado']);
        // Estudiante SÍ fue identificado previamente
        $this->assertSame($this->estudiante->id_estudiante, $log->informacion['estudiante_id']);
        $this->assertSame(999999, $log->informacion['examen_id']);
    }

    /**
     * 3. Tolerancia a Fallos: Si el servicio de auditoría lanza una excepción,
     * NO interrumpe la respuesta HTTP de la verificación y registra Log::error.
     */
    public function test_fallo_de_auditoria_no_tumba_la_verificacion(): void
    {
        Log::shouldReceive('error')
            ->atLeast()->once()
            ->withArgs(function ($message) {
                return str_contains($message, 'Error al registrar auditoría');
            });

        // Mockear AuditService para que lance una excepción simulada
        $mockAudit = $this->mock(AuditService::class);
        $mockAudit->shouldReceive('registrarVerificacion')
            ->once()
            ->andThrow(new \RuntimeException('Fallo simulado en almacenamiento de auditoría.'));

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->controlToken}",
            'Accept'        => 'application/json',
        ])->postJson('/api/verificaciones/documento', [
            'tipo'      => 'CI',
            'valor'     => '7192834',
            'examen_id' => $this->examen->id_examen,
        ]);

        // La verificación debe responder exitosamente con 200 sin caer en 500
        $response->assertStatus(200)
            ->assertJson([
                'metodo' => 'CI',
                'estudiante' => [
                    'ci' => '7192834',
                ],
            ]);
    }

    /**
     * 4. Inmutabilidad de AuditLog:
     * La actualización o eliminación desde Eloquent es rechazada mediante LogicException
     * y el registro permanece inalterado en la base de datos.
     */
    public function test_inmutabilidad_de_audit_logs(): void
    {
        $log = AuditLog::create([
            'id_usuario'  => $this->controlUser->id_usuario,
            'accion'      => 'VERIFICACION_IDENTIDAD',
            'entidad'     => 'estudiante',
            'id_registro' => (string) $this->estudiante->id_estudiante,
            'fecha_hora'  => now(),
            'informacion' => [
                'metodo'        => 'QR',
                'examen_id'     => $this->examen->id_examen,
                'estudiante_id' => $this->estudiante->id_estudiante,
                'resultado'     => 'EXITOSO',
            ],
        ]);

        $idAuditLog = $log->id_audit_log;
        $this->assertDatabaseHas('audit_logs', ['id_audit_log' => $idAuditLog]);

        // 4a. Intento de UPDATE -> LogicException
        $excepcionUpdate = false;
        try {
            $log->update(['accion' => 'ALTERADA_MALICIOSAMENTE']);
        } catch (\LogicException $e) {
            $excepcionUpdate = true;
            $this->assertStringContainsString('no se pueden modificar', $e->getMessage());
        }
        $this->assertTrue($excepcionUpdate, 'No se lanzó LogicException al intentar actualizar un AuditLog.');

        $log->refresh();
        $this->assertSame('VERIFICACION_IDENTIDAD', $log->accion);

        // 4b. Intento de DELETE -> LogicException
        $excepcionDelete = false;
        try {
            $log->delete();
        } catch (\LogicException $e) {
            $excepcionDelete = true;
            $this->assertStringContainsString('no se pueden eliminar', $e->getMessage());
        }
        $this->assertTrue($excepcionDelete, 'No se lanzó LogicException al intentar eliminar un AuditLog.');

        // El registro sigue existiendo en la base de datos
        $this->assertDatabaseHas('audit_logs', ['id_audit_log' => $idAuditLog]);
    }
}
