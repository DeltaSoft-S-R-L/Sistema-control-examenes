<?php

namespace Tests\Feature;

use App\Models\Asignatura;
use App\Models\Auditoria;
use App\Models\Carrera;
use App\Models\Estudiante;
use App\Models\Examen;
use App\Models\Facultad;
use App\Models\Habilitacion;
use App\Models\Ingreso;
use App\Models\Rol;
use App\Models\Usuario;
use App\Services\QrTokenService;
use App\Utilities\PasswordHasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
    private Estudiante $estudianteHabilitado;
    private Estudiante $estudianteInhabilitado;
    private Estudiante $estudianteNoAsociado;

    protected function setUp(): void
    {
        parent::setUp();

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

        // 4. Examen
        $this->examen = Examen::create([
            'id_asignatura'    => $asignatura->id_asignatura,
            'nombre'           => 'Examen Parcial de Prueba',
            'fecha'            => '2026-10-27',
            'hora_inicio'      => '08:00',
            'duracion_minutos' => 90,
            'estado'           => 'PROGRAMADO',
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
        Habilitacion::create([
            'id_estudiante' => $this->estudianteHabilitado->id_estudiante,
            'id_examen'     => $this->examen->id_examen,
            'estado'        => 'HABILITADO',
            'motivo'        => null,
        ]);

        Habilitacion::create([
            'id_estudiante' => $this->estudianteInhabilitado->id_estudiante,
            'id_examen'     => $this->examen->id_examen,
            'estado'        => 'NO_HABILITADO',
            'motivo'        => 'No cumple requisito de asistencia mínima (65%)',
        ]);
        // $this->estudianteNoAsociado no tiene registro en Habilitacion
    }

    // ─────────────────────────────────────────────────────────────────
    // 1. Estudiante Habilitado
    // ─────────────────────────────────────────────────────────────────
    public function test_estudiante_habilitado_retorna_estado_habilitado(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->tokenControl}")
            ->postJson('/api/verificar/documento', [
                'identificador' => $this->estudianteHabilitado->ci,
                'id_examen'     => $this->examen->id_examen,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'identificado' => true,
                'estudiante'   => [
                    'ci' => '1111111',
                ],
                'habilitacion' => [
                    'estado'     => 'HABILITADO',
                    'habilitado' => true,
                    'motivo'     => null,
                ],
            ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // 2. Estudiante Inhabilitado con Motivo
    // ─────────────────────────────────────────────────────────────────
    public function test_estudiante_inhabilitado_retorna_no_habilitado_con_motivo(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->tokenControl}")
            ->postJson('/api/verificar/documento', [
                'identificador' => $this->estudianteInhabilitado->codigo_universitario,
                'id_examen'     => $this->examen->id_examen,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'identificado' => true,
                'estudiante'   => [
                    'codigo_universitario' => '202000002',
                ],
                'habilitacion' => [
                    'estado'     => 'NO_HABILITADO',
                    'habilitado' => false,
                    'motivo'     => 'No cumple requisito de asistencia mínima (65%)',
                ],
            ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // 3. Estudiante No Asociado
    // ─────────────────────────────────────────────────────────────────
    public function test_estudiante_no_asociado_retorna_estado_no_asociado(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->tokenControl}")
            ->postJson('/api/verificar/documento', [
                'identificador' => $this->estudianteNoAsociado->ci,
                'id_examen'     => $this->examen->id_examen,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'identificado' => true,
                'habilitacion' => [
                    'estado'     => 'NO_ASOCIADO',
                    'habilitado' => false,
                ],
            ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // 4. Comprobar que QR y CI/SIS dan exactamente el mismo resultado
    // ─────────────────────────────────────────────────────────────────
    public function test_qr_y_ci_sis_dan_el_mismo_resultado_para_el_mismo_estudiante(): void
    {
        $qrToken = QrTokenService::generarToken($this->estudianteHabilitado);

        // Verificación vía QR
        $resQr = $this->withHeader('Authorization', "Bearer {$this->tokenControl}")
            ->postJson('/api/verificar/qr', [
                'token'     => $qrToken,
                'id_examen' => $this->examen->id_examen,
            ]);

        // Verificación vía CI
        $resCi = $this->withHeader('Authorization', "Bearer {$this->tokenControl}")
            ->postJson('/api/verificar/documento', [
                'identificador' => $this->estudianteHabilitado->ci,
                'id_examen'     => $this->examen->id_examen,
            ]);

        // Verificación vía Código SIS
        $resSis = $this->withHeader('Authorization', "Bearer {$this->tokenControl}")
            ->postJson('/api/verificar/ci-sis', [
                'ci_sis'    => $this->estudianteHabilitado->codigo_universitario,
                'id_examen' => $this->examen->id_examen,
            ]);

        $resQr->assertStatus(200);
        $resCi->assertStatus(200);
        $resSis->assertStatus(200);

        $jsonQr = $resQr->json();
        $jsonCi = $resCi->json();
        $jsonSis = $resSis->json();

        // Mismo resultado de habilitación y datos de estudiante
        $this->assertEquals($jsonQr['habilitacion'], $jsonCi['habilitacion']);
        $this->assertEquals($jsonCi['habilitacion'], $jsonSis['habilitacion']);
        $this->assertEquals($jsonQr['estudiante']['id_estudiante'], $jsonCi['estudiante']['id_estudiante']);
        $this->assertEquals($jsonCi['estudiante']['id_estudiante'], $jsonSis['estudiante']['id_estudiante']);
    }

    // ─────────────────────────────────────────────────────────────────
    // 5. Inhabilitar a un estudiante habilitado, volver a verificar y confirmar que cambia el resultado
    // ─────────────────────────────────────────────────────────────────
    public function test_inhabilitar_estudiante_habilitado_cambia_resultado_en_siguiente_verificacion(): void
    {
        // 1ª verificación: Habilitado
        $res1 = $this->withHeader('Authorization', "Bearer {$this->tokenControl}")
            ->postJson('/api/verificar/documento', [
                'identificador' => $this->estudianteHabilitado->ci,
                'id_examen'     => $this->examen->id_examen,
            ]);

        $this->assertTrue($res1->json('habilitacion.habilitado'));
        $this->assertEquals('HABILITADO', $res1->json('habilitacion.estado'));

        // Modificamos habilitación en BD a NO_HABILITADO con motivo
        Habilitacion::where('id_estudiante', $this->estudianteHabilitado->id_estudiante)
            ->where('id_examen', $this->examen->id_examen)
            ->update([
                'estado' => 'NO_HABILITADO',
                'motivo' => 'Inhabilitado por sanción disciplinaria',
            ]);

        // 2ª verificación: Cambió el resultado a NO_HABILITADO con motivo
        $res2 = $this->withHeader('Authorization', "Bearer {$this->tokenControl}")
            ->postJson('/api/verificar/documento', [
                'identificador' => $this->estudianteHabilitado->ci,
                'id_examen'     => $this->examen->id_examen,
            ]);

        $this->assertFalse($res2->json('habilitacion.habilitado'));
        $this->assertEquals('NO_HABILITADO', $res2->json('habilitacion.estado'));
        $this->assertEquals('Inhabilitado por sanción disciplinaria', $res2->json('habilitacion.motivo'));
    }

    // ─────────────────────────────────────────────────────────────────
    // 6. Verificar que NO se escribe ninguna fila en tablas de dominio (solo auditoria)
    // ─────────────────────────────────────────────────────────────────
    public function test_verificacion_no_escribe_en_tablas_de_dominio_solo_en_auditoria(): void
    {
        $totalIngresosPrevio = Ingreso::count();
        $totalHabilitacionesPrevio = Habilitacion::count();
        $totalEstudiantesPrevio = Estudiante::count();
        $totalExamenesPrevio = Examen::count();
        $totalAuditoriasPrevio = Auditoria::count();

        $qrToken = QrTokenService::generarToken($this->estudianteHabilitado);

        $response = $this->withHeader('Authorization', "Bearer {$this->tokenControl}")
            ->postJson('/api/verificar/qr', [
                'qr_token'  => $qrToken,
                'id_examen' => $this->examen->id_examen,
            ]);

        $response->assertStatus(200);

        // Tablas de dominio permanecen intactas
        $this->assertEquals($totalIngresosPrevio, Ingreso::count(), 'No debe registrarse fila en ingreso.');
        $this->assertEquals($totalHabilitacionesPrevio, Habilitacion::count(), 'No debe alterarse habilitacion.');
        $this->assertEquals($totalEstudiantesPrevio, Estudiante::count(), 'No debe alterarse estudiante.');
        $this->assertEquals($totalExamenesPrevio, Examen::count(), 'No debe alterarse examen.');

        // Solo se escribe en auditoria
        $this->assertEquals($totalAuditoriasPrevio + 1, Auditoria::count(), 'Debe crearse exactamente un registro en auditoria.');
    }

    // ─────────────────────────────────────────────────────────────────
    // 7. QR válido vs QR alterado vs Token con otro formato
    // ─────────────────────────────────────────────────────────────────
    public function test_verificacion_qr_valido_alterado_y_otro_formato(): void
    {
        $tokenValido = QrTokenService::generarToken($this->estudianteHabilitado);

        // QR Válido -> 200
        $resValido = $this->withHeader('Authorization', "Bearer {$this->tokenControl}")
            ->postJson('/api/verificar/qr', [
                'token'     => $tokenValido,
                'id_examen' => $this->examen->id_examen,
            ]);
        $resValido->assertStatus(200);

        // QR Alterado (modificamos firma) -> 422
        $tokenAlterado = substr($tokenValido, 0, -3) . 'xyz';
        $resAlterado = $this->withHeader('Authorization', "Bearer {$this->tokenControl}")
            ->postJson('/api/verificar/qr', [
                'token'     => $tokenAlterado,
                'id_examen' => $this->examen->id_examen,
            ]);
        $resAlterado->assertStatus(422)
            ->assertJson([
                'error'        => 'QR_ALTERADO',
                'identificado' => false,
            ]);

        // Token con otro formato (sin punto o corrupto) -> 422
        $resOtroFormato = $this->withHeader('Authorization', "Bearer {$this->tokenControl}")
            ->postJson('/api/verificar/qr', [
                'token'     => 'cadena-sin-formato-qr',
                'id_examen' => $this->examen->id_examen,
            ]);
        $resOtroFormato->assertStatus(422)
            ->assertJson([
                'error'        => 'FORMATO_INVALIDO',
                'identificado' => false,
            ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // 8. CI existente vs CI inexistente
    // ─────────────────────────────────────────────────────────────────
    public function test_verificacion_ci_existente_e_inexistente(): void
    {
        // Existente -> 200
        $resExistente = $this->withHeader('Authorization', "Bearer {$this->tokenControl}")
            ->postJson('/api/verificar/documento', [
                'identificador' => $this->estudianteHabilitado->ci,
                'id_examen'     => $this->examen->id_examen,
            ]);
        $resExistente->assertStatus(200)->assertJson(['identificado' => true]);

        // Inexistente -> 404
        $resInexistente = $this->withHeader('Authorization', "Bearer {$this->tokenControl}")
            ->postJson('/api/verificar/documento', [
                'identificador' => '9999999999',
                'id_examen'     => $this->examen->id_examen,
            ]);
        $resInexistente->assertStatus(404)->assertJson(['identificado' => false]);
    }

    // ─────────────────────────────────────────────────────────────────
    // 9. SIS existente vs SIS inexistente
    // ─────────────────────────────────────────────────────────────────
    public function test_verificacion_sis_existente_e_inexistente(): void
    {
        // Existente -> 200
        $resExistente = $this->withHeader('Authorization', "Bearer {$this->tokenControl}")
            ->postJson('/api/verificar/ci-sis', [
                'ci_sis'    => $this->estudianteInhabilitado->codigo_universitario,
                'id_examen' => $this->examen->id_examen,
            ]);
        $resExistente->assertStatus(200)->assertJson(['identificado' => true]);

        // Inexistente -> 404
        $resInexistente = $this->withHeader('Authorization', "Bearer {$this->tokenControl}")
            ->postJson('/api/verificar/ci-sis', [
                'ci_sis'    => 'SIS_NO_EXISTE_000',
                'id_examen' => $this->examen->id_examen,
            ]);
        $resInexistente->assertStatus(404)->assertJson(['identificado' => false]);
    }

    // ─────────────────────────────────────────────────────────────────
    // 10. Valores con espacios en blanco alrededor
    // ─────────────────────────────────────────────────────────────────
    public function test_verificacion_con_espacios_en_blanco(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->tokenControl}")
            ->postJson('/api/verificar/documento', [
                'identificador' => "   {$this->estudianteHabilitado->ci}   ",
                'id_examen'     => $this->examen->id_examen,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'identificado' => true,
                'estudiante'   => [
                    'ci' => $this->estudianteHabilitado->ci,
                ],
            ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // 11. Sin sesión (401)
    // ─────────────────────────────────────────────────────────────────
    public function test_verificacion_sin_sesion_retorna_401(): void
    {
        $response = $this->postJson('/api/verificar/documento', [
            'identificador' => $this->estudianteHabilitado->ci,
            'id_examen'     => $this->examen->id_examen,
        ]);

        $response->assertStatus(401);
    }

    // ─────────────────────────────────────────────────────────────────
    // 12. Rol Docente (403)
    // ─────────────────────────────────────────────────────────────────
    public function test_verificacion_con_rol_docente_retorna_403(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->tokenDocente}")
            ->postJson('/api/verificar/documento', [
                'identificador' => $this->estudianteHabilitado->ci,
                'id_examen'     => $this->examen->id_examen,
            ]);

        $response->assertStatus(403);
    }

    // ─────────────────────────────────────────────────────────────────
    // 13. Comprobar que cada verificación genera su registro de auditoría
    // ─────────────────────────────────────────────────────────────────
    public function test_cada_verificacion_genera_su_registro_de_auditoria(): void
    {
        Auditoria::query()->delete();

        // 1. Verificación exitosa por QR
        $qrToken = QrTokenService::generarToken($this->estudianteHabilitado);
        $this->withHeader('Authorization', "Bearer {$this->tokenControl}")
            ->postJson('/api/verificar/qr', [
                'token'     => $qrToken,
                'id_examen' => $this->examen->id_examen,
            ]);

        $auditoriaQr = Auditoria::where('accion', 'VERIFICACION_QR')->latest('id_auditoria')->first();
        $this->assertNotNull($auditoriaQr);
        $this->assertEquals($this->userControl->id_usuario, $auditoriaQr->id_usuario);
        $this->assertEquals('EXITO', $auditoriaQr->resultado);
        $this->assertEquals($this->estudianteHabilitado->id_estudiante, $auditoriaQr->id_registro);

        // 2. Verificación inhabilitada por CI
        $this->withHeader('Authorization', "Bearer {$this->tokenControl}")
            ->postJson('/api/verificar/documento', [
                'identificador' => $this->estudianteInhabilitado->ci,
                'id_examen'     => $this->examen->id_examen,
            ]);

        $auditoriaCi = Auditoria::where('accion', 'VERIFICACION_CI_SIS')->latest('id_auditoria')->first();
        $this->assertNotNull($auditoriaCi);
        $this->assertEquals('FALLO', $auditoriaCi->resultado);
        $this->assertStringContainsString('NO_HABILITADO', $auditoriaCi->descripcion);

        // 3. Verificación de estudiante inexistente
        $this->withHeader('Authorization', "Bearer {$this->tokenControl}")
            ->postJson('/api/verificar/documento', [
                'identificador' => 'CI_INEXISTENTE_999',
                'id_examen'     => $this->examen->id_examen,
            ]);

        $auditoriaInexistente = Auditoria::where('resultado', 'FALLO')
            ->where('id_registro', null)
            ->latest('id_auditoria')
            ->first();

        $this->assertNotNull($auditoriaInexistente);
        $this->assertStringContainsString('Identificación fallida', $auditoriaInexistente->descripcion);
    }
}
