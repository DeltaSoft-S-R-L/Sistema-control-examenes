<?php

declare(strict_types=1);

namespace Tests\Feature\Sprint4;

use App\Models\Asignatura;
use App\Models\AuditLog;
use App\Models\Carrera;
use App\Models\Estudiante;
use App\Models\Examen;
use App\Models\Facultad;
use App\Models\Habilitacion;
use App\Models\IntentoIngreso;
use App\Models\Rol;
use App\Models\Usuario;
use App\Services\QrTokenService;
use App\Utilities\PasswordHasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Ver03IntentoExamenAjenoTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $controlUser;
    private string $controlToken;

    private Examen $examenA;
    private Examen $examenB;
    private Estudiante $estudianteEnExamenB;
    private Estudiante $estudianteEnExamenA;

    private QrTokenService $qrService;
    private string $qrTokenEstudianteB;
    private string $qrTokenEstudianteA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->qrService = app(QrTokenService::class);

        // 1. Roles y Usuario operador de control de ingreso
        $rolControl = Rol::create([
            'nombre'      => 'CONTROL_INGRESO',
            'descripcion' => 'Personal de control de ingreso en puerta',
        ]);

        $this->controlUser = Usuario::create([
            'nombre'        => 'Rodrigo',
            'apellido'      => 'Vargas Guardia',
            'correo'        => 'rodrigo.vargas@umss.edu.bo',
            'username'      => 'rodrigo_control',
            'password_hash' => PasswordHasher::hash('ControlPass123!'),
            'id_rol'        => $rolControl->id_rol,
            'estado'        => 'ACTIVO',
        ]);
        $this->controlToken = $this->controlUser->createToken('token-control-puerta')->plainTextToken;

        // 2. Estructura académica
        $facultad = Facultad::create([
            'codigo' => 'FCYT',
            'nombre' => 'Facultad de Ciencias y Tecnología',
        ]);

        $carrera = Carrera::create([
            'codigo'      => 'SIS',
            'nombre'      => 'Ingeniería de Sistemas',
            'id_facultad' => $facultad->id_facultad,
        ]);

        $asignatura1 = Asignatura::create([
            'codigo'      => 'SIS-101',
            'nombre'      => 'Programación I',
            'descripcion' => 'Primer semestre',
        ]);

        $asignatura2 = Asignatura::create([
            'codigo'      => 'SIS-201',
            'nombre'      => 'Bases de Datos I',
            'descripcion' => 'Tercer semestre',
        ]);

        // 3. Exámenes A y B
        $this->examenA = Examen::create([
            'id_asignatura'    => $asignatura1->id_asignatura,
            'nombre'           => 'Examen Parcial Programación I',
            'fecha'            => '2026-11-20',
            'hora_inicio'      => '08:00:00',
            'duracion_minutos' => 90,
            'estado'           => 'PROGRAMADO',
        ]);

        $this->examenB = Examen::create([
            'id_asignatura'    => $asignatura2->id_asignatura,
            'nombre'           => 'Examen Parcial Bases de Datos I',
            'fecha'            => '2026-11-20',
            'hora_inicio'      => '10:00:00',
            'duracion_minutos' => 90,
            'estado'           => 'PROGRAMADO',
        ]);

        // 4. Estudiantes
        $this->estudianteEnExamenA = Estudiante::create([
            'ci'                   => '10000001',
            'nombre'               => 'Mateo',
            'apellido'             => 'Suarez',
            'codigo_universitario' => '202300001',
            'id_carrera'           => $carrera->id_carrera,
            'correo'               => 'mateo.suarez@umss.edu.bo',
            'estado'               => 'ACTIVO',
        ]);

        $this->estudianteEnExamenB = Estudiante::create([
            'ci'                   => '20000002',
            'nombre'               => 'Camila',
            'apellido'             => 'Fernandez',
            'codigo_universitario' => '202300002',
            'id_carrera'           => $carrera->id_carrera,
            'correo'               => 'camila.fernandez@umss.edu.bo',
            'estado'               => 'ACTIVO',
        ]);

        // 5. Habilitaciones asignadas
        Habilitacion::create([
            'id_estudiante' => $this->estudianteEnExamenA->id_estudiante,
            'id_examen'     => $this->examenA->id_examen,
            'estado'        => 'HABILITADO',
        ]);

        Habilitacion::create([
            'id_estudiante' => $this->estudianteEnExamenB->id_estudiante,
            'id_examen'     => $this->examenB->id_examen,
            'estado'        => 'HABILITADO',
        ]);

        // 6. Tokens QR
        $this->qrTokenEstudianteA = $this->qrService->generar($this->estudianteEnExamenA);
        $this->qrTokenEstudianteB = $this->qrService->generar($this->estudianteEnExamenB);
    }

    /**
     * Caso 1 (QR): Estudiante asociado al examen envía QR -> Respuesta 200 OK, no crea registros en intento_ingreso.
     */
    public function test_estudiante_asociado_envia_qr_retorna_200_y_no_crea_intento_ingreso(): void
    {
        $conteoIntentosPrevio = IntentoIngreso::count();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->controlToken}",
            'Accept'        => 'application/json',
        ])->postJson('/api/verificaciones/qr', [
            'token'     => $this->qrTokenEstudianteA,
            'examen_id' => $this->examenA->id_examen,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'metodo' => 'QR',
                'estudiante' => [
                    'id'         => $this->estudianteEnExamenA->id_estudiante,
                    'ci'         => '10000001',
                    'codigo_sis' => '202300001',
                ],
                'examen' => [
                    'id' => $this->examenA->id_examen,
                ],
            ]);

        $this->assertSame($conteoIntentosPrevio, IntentoIngreso::count(), 'No debe registrarse fila en intento_ingreso cuando es legítimo.');
    }

    /**
     * Caso 1 (CI/SIS): Estudiante asociado al examen envía CI -> Respuesta 200 OK, no crea registros en intento_ingreso.
     */
    public function test_estudiante_asociado_envia_ci_retorna_200_y_no_crea_intento_ingreso(): void
    {
        $conteoIntentosPrevio = IntentoIngreso::count();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->controlToken}",
            'Accept'        => 'application/json',
        ])->postJson('/api/verificaciones/documento', [
            'tipo'      => 'CI',
            'valor'     => '10000001',
            'examen_id' => $this->examenA->id_examen,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'metodo' => 'CI',
                'estudiante' => [
                    'id' => $this->estudianteEnExamenA->id_estudiante,
                ],
                'examen' => [
                    'id' => $this->examenA->id_examen,
                ],
            ]);

        $this->assertSame($conteoIntentosPrevio, IntentoIngreso::count());
    }

    /**
     * Caso 2 (QR): Estudiante válido intenta ingresar a un examen al que NO está habilitado
     * -> Respuesta HTTP 422 con código NO_ASOCIADO,
     * assert en BD de que existe el registro en intento_ingreso con motivo = 'EXAMEN_NO_CORRESPONDIENTE',
     * y assert en audit_logs con la acción INTENTO_INGRESO_EXAMEN_AJENO.
     */
    public function test_estudiante_intenta_ingresar_a_examen_ajeno_por_qr_retorna_422_y_registra_intento_y_auditoria(): void
    {
        $conteoIntentosPrevio = IntentoIngreso::count();
        $conteoAuditoriasPrevio = AuditLog::count();

        // Estudiante B (habilitado en examen B) intenta ingresar al examen A usando QR
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->controlToken}",
            'Accept'        => 'application/json',
        ])->postJson('/api/verificaciones/qr', [
            'token'     => $this->qrTokenEstudianteB,
            'examen_id' => $this->examenA->id_examen,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'El estudiante no pertenece a este examen.',
                'code'    => 'NO_ASOCIADO',
                'error'   => 'NO_ASOCIADO',
            ]);

        // Verificar persistencia en intento_ingreso
        $this->assertSame($conteoIntentosPrevio + 1, IntentoIngreso::count(), 'Debe crearse exactamente un registro en intento_ingreso.');

        $this->assertDatabaseHas('intento_ingreso', [
            'id_estudiante' => $this->estudianteEnExamenB->id_estudiante,
            'id_examen'     => $this->examenA->id_examen,
            'motivo'        => 'EXAMEN_NO_CORRESPONDIENTE',
            'id_usuario'    => $this->controlUser->id_usuario,
        ]);

        // Verificar registro en audit_logs
        $this->assertSame($conteoAuditoriasPrevio + 1, AuditLog::count(), 'Debe crearse un registro en audit_logs.');

        $ultimoLog = AuditLog::latest('id_audit_log')->first();
        $this->assertNotNull($ultimoLog);
        $this->assertSame('INTENTO_INGRESO_EXAMEN_AJENO', $ultimoLog->accion);
        $this->assertSame('estudiante', $ultimoLog->entidad);
        $this->assertSame((string) $this->estudianteEnExamenB->id_estudiante, $ultimoLog->id_registro);
        $this->assertSame($this->controlUser->id_usuario, $ultimoLog->id_usuario);
        $this->assertSame('EXAMEN_NO_CORRESPONDIENTE', $ultimoLog->informacion['motivo'] ?? null);
        $this->assertSame($this->examenA->id_examen, $ultimoLog->informacion['id_examen_intentado'] ?? null);
        $this->assertSame('QR', $ultimoLog->informacion['metodo'] ?? null);
    }

    /**
     * Caso 2 (Documento): Estudiante intenta ingresar a examen ajeno por CI/SIS
     * -> Respuesta HTTP 422 con código NO_ASOCIADO y registro en intento_ingreso y audit_logs.
     */
    public function test_estudiante_intenta_ingresar_a_examen_ajeno_por_documento_retorna_422_y_registra_intento_y_auditoria(): void
    {
        // Estudiante B intenta ingresar al examen A usando CI
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->controlToken}",
            'Accept'        => 'application/json',
        ])->postJson('/api/verificaciones/documento', [
            'tipo'      => 'CI',
            'valor'     => '20000002',
            'examen_id' => $this->examenA->id_examen,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'El estudiante no pertenece a este examen.',
                'code'    => 'NO_ASOCIADO',
                'error'   => 'NO_ASOCIADO',
            ]);

        $this->assertDatabaseHas('intento_ingreso', [
            'id_estudiante' => $this->estudianteEnExamenB->id_estudiante,
            'id_examen'     => $this->examenA->id_examen,
            'motivo'        => 'EXAMEN_NO_CORRESPONDIENTE',
            'id_usuario'    => $this->controlUser->id_usuario,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'accion'      => 'INTENTO_INGRESO_EXAMEN_AJENO',
            'entidad'     => 'estudiante',
            'id_registro' => (string) $this->estudianteEnExamenB->id_estudiante,
            'id_usuario'  => $this->controlUser->id_usuario,
        ]);
    }

    /**
     * Caso 3: Verificar que el id_usuario registrado en intento_ingreso coincida con el usuario
     * autenticado que envió la petición.
     */
    public function test_id_usuario_registrado_en_intento_ingreso_coincide_con_usuario_autenticado(): void
    {
        // Crear un segundo operador de control
        $segundoOperador = Usuario::create([
            'nombre'        => 'Valeria',
            'apellido'      => 'Almaraz',
            'correo'        => 'valeria.control2@umss.edu.bo',
            'username'      => 'valeria_control',
            'password_hash' => PasswordHasher::hash('ControlPass456!'),
            'id_rol'        => $this->controlUser->id_rol,
            'estado'        => 'ACTIVO',
        ]);
        $tokenSegundoOperador = $segundoOperador->createToken('token-valeria')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$tokenSegundoOperador}",
            'Accept'        => 'application/json',
        ])->postJson('/api/verificaciones/qr', [
            'token'     => $this->qrTokenEstudianteB,
            'examen_id' => $this->examenA->id_examen,
        ]);

        $response->assertStatus(422);

        $intento = IntentoIngreso::where('id_estudiante', $this->estudianteEnExamenB->id_estudiante)
            ->where('id_examen', $this->examenA->id_examen)
            ->latest('id_intento')
            ->first();

        $this->assertNotNull($intento);
        $this->assertSame(
            $segundoOperador->id_usuario,
            $intento->id_usuario,
            'El id_usuario en intento_ingreso debe coincidir exactamente con el usuario autenticado que envió la petición.'
        );
    }
}
