<?php

namespace Tests\Feature\Sprint4;

use App\Enums\AccionAuditoria;
use App\Enums\CodigoErrorVerificacion;
use App\Enums\MotivoIntentoIngreso;
use App\Models\Ambiente;
use App\Models\AsignacionAmbiente;
use App\Models\AuditLog;
use App\Models\Asignatura;
use App\Models\Carrera;
use App\Models\Estudiante;
use App\Models\Examen;
use App\Models\Facultad;
use App\Models\Habilitacion;
use App\Models\Ingreso;
use App\Models\IntentoIngreso;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\RolPermiso;
use App\Models\Usuario;
use App\Services\QrTokenService;
use App\Utilities\PasswordHasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * VER-03 · Rechazo de estudiantes a exámenes ajenos
 *
 * Cubre tres escenarios críticos de control de acceso:
 *   (A) Estudiante de la materia correcta → 200 OK
 *   (B) Estudiante registrado en otra materia/examen → rechazo + log
 *   (C) Estudiante sin ninguna materia asignada → NO_ASOCIADO
 *
 * También verifica que registrar un ingreso en el aula equivocada
 * dispara el evento DISCREPANCIA_AMBIENTE_INGRESO en audit_logs.
 */
class Ver03ExamenNoCorrespondienteTest extends TestCase
{
    use RefreshDatabase;

    // ── Roles y usuario ─────────────────────────────────────────────
    private Rol     $rolControl;
    private Usuario $userControl;
    private string  $tokenControl;

    // ── Estructura académica: Examen A ───────────────────────────────
    private Examen            $examenA;        // Ing. de Software
    private Ambiente          $ambienteA;
    private AsignacionAmbiente $asignacionA;

    // ── Estructura académica: Examen B ───────────────────────────────
    private Examen            $examenB;        // Redes de Computadoras
    private Ambiente          $ambienteB;
    private AsignacionAmbiente $asignacionB;

    // ── Estudiantes ──────────────────────────────────────────────────
    private Estudiante $estudianteDeA;         // habilitado en Examen A
    private Estudiante $estudianteDeB;         // habilitado en Examen B (ajeno a A)
    private Estudiante $estudianteSinMateria;  // sin ninguna habilitación

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Rol, permisos y usuario de control ──────────────────────
        $this->rolControl = Rol::create([
            'nombre'      => 'CONTROL_INGRESO',
            'descripcion' => 'Personal de control de ingreso',
        ]);

        // Asignar permisos necesarios al rol CONTROL_INGRESO
        $permisos = [
            'REGISTRAR_INGRESOS',
            'REGISTRAR_INTENTOS',
            'REGISTRAR_EXPULSIONES',
            'REGISTRAR_INCIDENCIAS',
            'CONSULTAR_REPORTES',
        ];
        foreach ($permisos as $nombrePermiso) {
            $permiso = \App\Models\Permiso::create(['nombre' => $nombrePermiso, 'descripcion' => null]);
            \Illuminate\Support\Facades\DB::table('rol_permiso')->insert([
                'id_rol'     => $this->rolControl->id_rol,
                'id_permiso' => $permiso->id_permiso,
            ]);
        }

        $this->userControl = Usuario::create([
            'nombre'        => 'Carlos',
            'apellido'      => 'Control',
            'correo'        => 'carlos.control@universidad.edu',
            'username'      => 'control1',
            'password_hash' => PasswordHasher::hash('Password123'),
            'id_rol'        => $this->rolControl->id_rol,
            'estado'        => 'ACTIVO',
        ]);
        $this->tokenControl = $this->userControl->createToken('tok-control')->plainTextToken;

        // 2. Estructura académica ────────────────────────────────────
        $facultad = Facultad::create(['codigo' => 'FCYT', 'nombre' => 'Ciencias y Tecnología']);
        $carrera  = Carrera::create(['id_facultad' => $facultad->id_facultad, 'codigo' => '411702', 'nombre' => 'Ing. Sistemas']);

        $asignaturaA = Asignatura::create(['codigo' => 'SIS-101', 'nombre' => 'Ingeniería de Software',    'descripcion' => null]);
        $asignaturaB = Asignatura::create(['codigo' => 'SIS-202', 'nombre' => 'Redes de Computadoras',     'descripcion' => null]);

        // 3. Exámenes ────────────────────────────────────────────────
        $this->examenA = Examen::create([
            'id_asignatura'    => $asignaturaA->id_asignatura,
            'nombre'           => 'Parcial 1 – Ing. Software',
            'fecha'            => '2026-11-20',
            'hora_inicio'      => '08:00',
            'duracion_minutos' => 90,
            'estado'           => 'PROGRAMADO',
        ]);

        $this->examenB = Examen::create([
            'id_asignatura'    => $asignaturaB->id_asignatura,
            'nombre'           => 'Parcial 1 – Redes',
            'fecha'            => '2026-11-20',
            'hora_inicio'      => '14:00',
            'duracion_minutos' => 90,
            'estado'           => 'PROGRAMADO',
        ]);

        // 4. Ambientes y asignaciones ────────────────────────────────
        $this->ambienteA = Ambiente::create([
            'codigo'    => 'AULA-01',
            'nombre'    => 'Aula 01',
            'ubicacion' => 'Bloque A',
            'capacidad' => 50,
            'estado'    => 'DISPONIBLE',
        ]);

        $this->ambienteB = Ambiente::create([
            'codigo'    => 'AULA-02',
            'nombre'    => 'Aula 02',
            'ubicacion' => 'Bloque B',
            'capacidad' => 40,
            'estado'    => 'DISPONIBLE',
        ]);

        $this->asignacionA = AsignacionAmbiente::create([
            'id_examen'  => $this->examenA->id_examen,
            'id_ambiente' => $this->ambienteA->id_ambiente,
        ]);

        $this->asignacionB = AsignacionAmbiente::create([
            'id_examen'  => $this->examenB->id_examen,
            'id_ambiente' => $this->ambienteB->id_ambiente,
        ]);

        // 5. Estudiantes ─────────────────────────────────────────────
        $this->estudianteDeA = Estudiante::create([
            'ci'                   => '1010101',
            'nombre'               => 'Ana',
            'apellido'             => 'Torres',
            'codigo_universitario' => '202010001',
            'id_carrera'           => $carrera->id_carrera,
            'correo'               => 'ana.torres@universidad.edu',
            'estado'               => 'ACTIVO',
        ]);

        $this->estudianteDeB = Estudiante::create([
            'ci'                   => '2020202',
            'nombre'               => 'Luis',
            'apellido'             => 'Mamani',
            'codigo_universitario' => '202010002',
            'id_carrera'           => $carrera->id_carrera,
            'correo'               => 'luis.mamani@universidad.edu',
            'estado'               => 'ACTIVO',
        ]);

        $this->estudianteSinMateria = Estudiante::create([
            'ci'                   => '3030303',
            'nombre'               => 'Rosa',
            'apellido'             => 'Quispe',
            'codigo_universitario' => '202010003',
            'id_carrera'           => $carrera->id_carrera,
            'correo'               => 'rosa.quispe@universidad.edu',
            'estado'               => 'ACTIVO',
        ]);

        // 6. Habilitaciones ──────────────────────────────────────────
        // estudianteDeA → habilitado en Examen A
        Habilitacion::create([
            'id_estudiante' => $this->estudianteDeA->id_estudiante,
            'id_examen'     => $this->examenA->id_examen,
            'estado'        => 'HABILITADO',
        ]);

        // estudianteDeB → habilitado en Examen B (otro examen)
        Habilitacion::create([
            'id_estudiante' => $this->estudianteDeB->id_estudiante,
            'id_examen'     => $this->examenB->id_examen,
            'estado'        => 'HABILITADO',
        ]);

        // estudianteSinMateria → sin ningún registro de habilitación
    }

    // ─────────────────────────────────────────────────────────────────
    // A. Estudiante de la materia correcta → 200 OK
    // ─────────────────────────────────────────────────────────────────

    public function test_estudiante_de_la_materia_correcta_es_verificado_ok(): void
    {
        $response = $this->withToken($this->tokenControl)
            ->postJson('/api/verificaciones/documento', [
                'tipo'      => 'CI',
                'valor'     => $this->estudianteDeA->ci,
                'examen_id' => $this->examenA->id_examen,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'metodo'     => 'CI',
                'estudiante' => ['ci' => '1010101'],
                'examen'     => ['id' => $this->examenA->id_examen],
            ]);
    }

    public function test_estudiante_correcto_por_qr_retorna_200(): void
    {
        $token = app(QrTokenService::class)->generar($this->estudianteDeA);

        $response = $this->withToken($this->tokenControl)
            ->postJson('/api/verificaciones/qr', [
                'token'     => $token,
                'examen_id' => $this->examenA->id_examen,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'metodo'     => 'QR',
                'estudiante' => ['ci' => '1010101'],
                'examen'     => ['id' => $this->examenA->id_examen],
            ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // B. Estudiante de otra materia → 422 (NO_ASOCIADO) + log
    // ─────────────────────────────────────────────────────────────────

    public function test_estudiante_de_otra_materia_es_rechazado_en_examen_ajeno(): void
    {
        // estudianteDeB intenta entrar al ExamenA (donde no está inscripto)
        $response = $this->withToken($this->tokenControl)
            ->postJson('/api/verificaciones/documento', [
                'tipo'      => 'CI',
                'valor'     => $this->estudianteDeB->ci,
                'examen_id' => $this->examenA->id_examen,  // examen ajeno
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'code'  => CodigoErrorVerificacion::NO_ASOCIADO->value,
                'error' => CodigoErrorVerificacion::NO_ASOCIADO->value,
            ]);
    }

    public function test_rechazo_por_examen_ajeno_genera_registro_en_auditoria(): void
    {
        $auditAntes = AuditLog::count();
        $intentosAntes = IntentoIngreso::count();

        $response = $this->withToken($this->tokenControl)
            ->postJson('/api/verificaciones/documento', [
                'tipo'      => 'CI',
                'valor'     => $this->estudianteDeB->ci,
                'examen_id' => $this->examenA->id_examen,
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('code', CodigoErrorVerificacion::NO_ASOCIADO->value);

        // Debe haberse creado registro de auditoría e intento rechazado
        $this->assertGreaterThan($auditAntes, AuditLog::count());
        $this->assertGreaterThan($intentosAntes, IntentoIngreso::count());

        $log = AuditLog::latest('id_audit_log')->first();
        $this->assertNotNull($log);
        $this->assertEquals(AccionAuditoria::INTENTO_INGRESO_EXAMEN_AJENO->value, $log->accion);
        $this->assertEquals('estudiante', $log->entidad);
        $this->assertEquals((string) $this->estudianteDeB->id_estudiante, $log->id_registro);

        $intento = IntentoIngreso::latest('id_intento')->first();
        $this->assertNotNull($intento);
        $this->assertEquals(MotivoIntentoIngreso::EXAMEN_NO_CORRESPONDIENTE->value, $intento->motivo);
    }

    public function test_estudiante_de_otra_materia_por_qr_en_examen_ajeno_es_rechazado(): void
    {
        $token = app(QrTokenService::class)->generar($this->estudianteDeB);

        $response = $this->withToken($this->tokenControl)
            ->postJson('/api/verificaciones/qr', [
                'token'     => $token,
                'examen_id' => $this->examenA->id_examen,
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'code'  => CodigoErrorVerificacion::NO_ASOCIADO->value,
                'error' => CodigoErrorVerificacion::NO_ASOCIADO->value,
            ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // C. Estudiante sin ninguna materia asignada → 422 NO_ASOCIADO
    // ─────────────────────────────────────────────────────────────────

    public function test_estudiante_sin_ninguna_materia_asignada_retorna_no_asociado(): void
    {
        $response = $this->withToken($this->tokenControl)
            ->postJson('/api/verificaciones/documento', [
                'tipo'      => 'CI',
                'valor'     => $this->estudianteSinMateria->ci,
                'examen_id' => $this->examenA->id_examen,
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'code'  => CodigoErrorVerificacion::NO_ASOCIADO->value,
                'error' => CodigoErrorVerificacion::NO_ASOCIADO->value,
            ]);
    }

    public function test_estudiante_sin_materia_en_ningun_examen_es_rechazado_en_ambos(): void
    {
        foreach ([$this->examenA->id_examen, $this->examenB->id_examen] as $idExamen) {
            $response = $this->withToken($this->tokenControl)
                ->postJson('/api/verificaciones/documento', [
                    'tipo'      => 'CI',
                    'valor'     => $this->estudianteSinMateria->ci,
                    'examen_id' => $idExamen,
                ]);

            $response->assertStatus(422)
                ->assertJson([
                    'code' => CodigoErrorVerificacion::NO_ASOCIADO->value,
                ]);
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // D. Ingreso duplicado → debe devolver 409 REINGRESO_NO_PERMITIDO (no 500)
    // ─────────────────────────────────────────────────────────────────

    public function test_ingreso_duplicado_devuelve_409_no_500(): void
    {
        $habilitacion = Habilitacion::where('id_estudiante', $this->estudianteDeA->id_estudiante)
            ->where('id_examen', $this->examenA->id_examen)
            ->first();

        $payload = [
            'id_examen'              => $this->examenA->id_examen,
            'id_habilitacion'        => $habilitacion->id_habilitacion,
            'id_asignacion_ambiente' => $this->asignacionA->id_asignacion_ambiente,
        ];

        // Primer ingreso → 201
        $r1 = $this->withToken($this->tokenControl)->postJson('/api/ingresos', $payload);
        $r1->assertStatus(201);

        // Segundo ingreso (duplicado, ING-03) → 409 Conflict, no 500
        $r2 = $this->withToken($this->tokenControl)->postJson('/api/ingresos', $payload);
        $r2->assertStatus(409)
            ->assertJsonPath('code', CodigoErrorVerificacion::REINGRESO_NO_PERMITIDO->value);
    }

    // ─────────────────────────────────────────────────────────────────
    // E. Discrepancia de ambiente → audit_log DISCREPANCIA_AMBIENTE_INGRESO
    // ─────────────────────────────────────────────────────────────────

    public function test_ingreso_en_aula_incorrecta_registra_discrepancia_en_audit_logs(): void
    {
        $habilitacion = Habilitacion::where('id_estudiante', $this->estudianteDeA->id_estudiante)
            ->where('id_examen', $this->examenA->id_examen)
            ->first();

        // El estudiante A está asignado a asignacionA (ambienteA),
        // pero intentamos registrarlo en asignacionB (ambienteB, examen B)
        // → debe fallar con 422 y dejar traza en audit_logs
        $response = $this->withToken($this->tokenControl)
            ->postJson('/api/ingresos', [
                'id_examen'              => $this->examenA->id_examen,
                'id_habilitacion'        => $habilitacion->id_habilitacion,
                'id_asignacion_ambiente' => $this->asignacionB->id_asignacion_ambiente, // aula incorrecta
            ]);

        // La combinación (asignacion_ambiente, examen) no pertenece al mismo examen → error de validación
        $response->assertStatus(422);

        // Verificar que se registró la discrepancia en audit_logs
        $log = AuditLog::where('accion', 'DISCREPANCIA_AMBIENTE_INGRESO')
            ->latest('id_audit_log')
            ->first();

        $this->assertNotNull($log, 'Debe existir un registro DISCREPANCIA_AMBIENTE_INGRESO en audit_logs');
        $this->assertEquals((string) $this->estudianteDeA->id_estudiante, $log->id_registro);

        $info = $log->informacion;
        $this->assertArrayHasKey('ambiente_intentado',  $info);
        $this->assertArrayHasKey('ambiente_asignado',   $info);
        $this->assertArrayHasKey('id_estudiante',       $info);
        $this->assertArrayHasKey('id_usuario_control',  $info);
    }
}
