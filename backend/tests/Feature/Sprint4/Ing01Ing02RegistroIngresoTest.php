<?php

declare(strict_types=1);

namespace Tests\Feature\Sprint4;

use App\Enums\AccionAuditoria;
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
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Ing01Ing02RegistroIngresoTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $controlUser;
    private string $controlToken;

    private Usuario $docenteUser;
    private string $docenteToken;

    private Examen $examen;
    private Ambiente $ambiente;
    private AsignacionAmbiente $asignacionAmbiente;

    private Estudiante $estudianteHabilitado;
    private Habilitacion $habilitacionValida;

    private Estudiante $estudianteInhabilitado;
    private Habilitacion $habilitacionInvalida;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Roles
        $rolControl = Rol::create([
            'nombre'      => 'CONTROL_INGRESO',
            'descripcion' => 'Personal de control en puerta',
        ]);

        $rolDocente = Rol::create([
            'nombre'      => 'DOCENTE',
            'descripcion' => 'Docente universitario',
        ]);

        // 2. Usuarios
        $this->controlUser = Usuario::create([
            'nombre'        => 'Fernando',
            'apellido'      => 'Guzmán',
            'correo'        => 'fernando.control@umss.edu.bo',
            'username'      => 'fernando_control',
            'password_hash' => PasswordHasher::hash('PassControl123!'),
            'id_rol'        => $rolControl->id_rol,
            'estado'        => 'ACTIVO',
        ]);
        $this->controlToken = $this->controlUser->createToken('token-control')->plainTextToken;

        $this->docenteUser = Usuario::create([
            'nombre'        => 'Mariana',
            'apellido'      => 'Docente',
            'correo'        => 'mariana.docente@umss.edu.bo',
            'username'      => 'mariana_docente',
            'password_hash' => PasswordHasher::hash('PassDocente123!'),
            'id_rol'        => $rolDocente->id_rol,
            'estado'        => 'ACTIVO',
        ]);
        $this->docenteToken = $this->docenteUser->createToken('token-docente')->plainTextToken;

        // 3. Estructura Académica
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
            'codigo'      => 'SIS-101',
            'nombre'      => 'Programación I',
            'descripcion' => 'Primer semestre',
        ]);

        // 4. Examen
        $this->examen = Examen::create([
            'id_asignatura'    => $asignatura->id_asignatura,
            'nombre'           => 'Examen Parcial Programación I',
            'fecha'            => '2026-11-20',
            'hora_inicio'      => '08:00:00',
            'duracion_minutos' => 90,
            'estado'           => 'PROGRAMADO',
        ]);

        // 5. Ambiente y Asignación
        $this->ambiente = Ambiente::create([
            'codigo'    => '624',
            'nombre'    => 'Aula 624',
            'ubicacion' => 'Edificio Nuevo',
            'capacidad' => 50,
            'estado'    => 'DISPONIBLE',
        ]);

        $this->asignacionAmbiente = AsignacionAmbiente::create([
            'id_examen'   => $this->examen->id_examen,
            'id_ambiente' => $this->ambiente->id_ambiente,
        ]);

        // 6. Estudiantes y Habilitaciones
        $this->estudianteHabilitado = Estudiante::create([
            'ci'                   => '7712345',
            'nombre'               => 'Ana',
            'apellido'             => 'Torres',
            'codigo_universitario' => '202400010',
            'id_carrera'           => $carrera->id_carrera,
            'correo'               => 'ana.torres@umss.edu.bo',
            'estado'               => 'ACTIVO',
        ]);

        $this->habilitacionValida = Habilitacion::create([
            'id_estudiante' => $this->estudianteHabilitado->id_estudiante,
            'id_examen'     => $this->examen->id_examen,
            'estado'        => 'HABILITADO',
        ]);

        $this->estudianteInhabilitado = Estudiante::create([
            'ci'                   => '8812345',
            'nombre'               => 'Pedro',
            'apellido'             => 'Molina',
            'codigo_universitario' => '202400020',
            'id_carrera'           => $carrera->id_carrera,
            'correo'               => 'pedro.molina@umss.edu.bo',
            'estado'               => 'ACTIVO',
        ]);

        $this->habilitacionInvalida = Habilitacion::create([
            'id_estudiante'  => $this->estudianteInhabilitado->id_estudiante,
            'id_examen'      => $this->examen->id_examen,
            'estado'         => 'NO_HABILITADO',
            'motivo_tipo'    => 'MORA',
            'motivo_detalle' => 'Pendiente pago de matrícula',
        ]);
    }

    /**
     * Caso 1 (Ingreso exitoso - ING-01):
     * Operador con rol CONTROL_INGRESO registra el ingreso de un estudiante HABILITADO.
     * Retorna 201 Created y se verifica en base de datos que id_usuario sea exactamente el del operador autenticado.
     */
    public function test_operador_control_registra_ingreso_exitoso_vinculando_su_id_usuario(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->controlToken}",
            'Accept'        => 'application/json',
        ])->postJson('/api/ingresos', [
            'id_examen'              => $this->examen->id_examen,
            'id_habilitacion'        => $this->habilitacionValida->id_habilitacion,
            'id_asignacion_ambiente' => $this->asignacionAmbiente->id_asignacion_ambiente,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'id_examen'              => $this->examen->id_examen,
                'id_habilitacion'        => $this->habilitacionValida->id_habilitacion,
                'id_asignacion_ambiente' => $this->asignacionAmbiente->id_asignacion_ambiente,
                'id_usuario'             => $this->controlUser->id_usuario,
            ]);

        // Verificación en base de datos: id_usuario debe ser exactamente el del operador autenticado
        $this->assertDatabaseHas('ingreso', [
            'id_habilitacion'        => $this->habilitacionValida->id_habilitacion,
            'id_examen'              => $this->examen->id_examen,
            'id_asignacion_ambiente' => $this->asignacionAmbiente->id_asignacion_ambiente,
            'id_usuario'             => $this->controlUser->id_usuario,
        ]);

        // Auditoría registrada
        $this->assertDatabaseHas('audit_logs', [
            'accion'     => 'REGISTRO_INGRESO_EXAMEN',
            'entidad'    => 'ingreso',
            'id_usuario' => $this->controlUser->id_usuario,
        ]);
    }

    /**
     * Caso 2 (Inmutabilidad de Timestamp - ING-02):
     * El cliente envía en el payload "fecha_hora": "2020-01-01 05:00:00".
     * Verificar que la base de datos ignore ese valor y almacene el timestamp actual del servidor (now()).
     */
    public function test_servidor_ignora_fecha_hora_enviada_por_cliente_y_usa_now(): void
    {
        $fechaPasadaFalsa = '2020-01-01 05:00:00';
        $tiempoAntesDePeticion = Carbon::now()->subSeconds(2);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->controlToken}",
            'Accept'        => 'application/json',
        ])->postJson('/api/ingresos', [
            'id_examen'              => $this->examen->id_examen,
            'id_habilitacion'        => $this->habilitacionValida->id_habilitacion,
            'id_asignacion_ambiente' => $this->asignacionAmbiente->id_asignacion_ambiente,
            'fecha_hora'             => $fechaPasadaFalsa, // Intento de manipulación de marca de tiempo
        ]);

        $response->assertStatus(201);

        $ingreso = Ingreso::where('id_habilitacion', $this->habilitacionValida->id_habilitacion)->first();
        $this->assertNotNull($ingreso);

        // La fecha persistida NO debe ser la fecha manipulada
        $this->assertDatabaseMissing('ingreso', [
            'id_habilitacion' => $this->habilitacionValida->id_habilitacion,
            'fecha_hora'      => $fechaPasadaFalsa,
        ]);

        // La fecha persistida debe ser actual (mayor o igual al momento de la prueba)
        $this->assertTrue(
            $ingreso->fecha_hora->greaterThanOrEqualTo($tiempoAntesDePeticion),
            'La fecha_hora debe ser generada por el servidor (now()), ignorando el payload del cliente.'
        );
    }

    /**
     * Caso 3 (Seguridad RBAC):
     * Un usuario con rol DOCENTE o sin autenticación intenta hacer POST /api/ingresos y recibe 403 / 401.
     */
    public function test_usuario_docente_o_sin_autenticacion_es_rechazado_con_403_o_401(): void
    {
        // 3a. Sin autenticación -> 401 Unauthorized
        $respSinAuth = $this->withHeaders([
            'Accept' => 'application/json',
        ])->postJson('/api/ingresos', [
            'id_examen'              => $this->examen->id_examen,
            'id_habilitacion'        => $this->habilitacionValida->id_habilitacion,
            'id_asignacion_ambiente' => $this->asignacionAmbiente->id_asignacion_ambiente,
        ]);

        $respSinAuth->assertStatus(401);

        // 3b. Con rol DOCENTE -> 403 Forbidden
        $respDocente = $this->withHeaders([
            'Authorization' => "Bearer {$this->docenteToken}",
            'Accept'        => 'application/json',
        ])->postJson('/api/ingresos', [
            'id_examen'              => $this->examen->id_examen,
            'id_habilitacion'        => $this->habilitacionValida->id_habilitacion,
            'id_asignacion_ambiente' => $this->asignacionAmbiente->id_asignacion_ambiente,
        ]);

        $respDocente->assertStatus(403)
            ->assertJson([
                'message' => 'No tiene permisos para realizar esta acción.',
            ]);

        // Comprobar que no se creó registro en ingreso
        $this->assertSame(0, Ingreso::count());
    }

    /**
     * Caso 4 (Estudiante Inhabilitado):
     * Intentar registrar ingreso de una habilitación con estado NO_HABILITADO retorna 403 y no crea fila en ingreso.
     * Además registra el intento indebido en intento_ingreso.
     */
    public function test_estudiante_no_habilitado_retorna_403_y_registra_intento_indebido(): void
    {
        $conteoIngresosPrevio = Ingreso::count();
        $conteoIntentosPrevio = IntentoIngreso::count();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->controlToken}",
            'Accept'        => 'application/json',
        ])->postJson('/api/ingresos', [
            'id_examen'              => $this->examen->id_examen,
            'id_habilitacion'        => $this->habilitacionInvalida->id_habilitacion,
            'id_asignacion_ambiente' => $this->asignacionAmbiente->id_asignacion_ambiente,
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'El estudiante no se encuentra habilitado para este examen.',
                'code'    => 'NO_HABILITADO',
                'error'   => 'NO_HABILITADO',
            ]);

        // No debe crear fila en ingreso
        $this->assertSame($conteoIngresosPrevio, Ingreso::count());

        // Debe registrar intento en intento_ingreso
        $this->assertSame($conteoIntentosPrevio + 1, IntentoIngreso::count());
        $this->assertDatabaseHas('intento_ingreso', [
            'id_estudiante'          => $this->estudianteInhabilitado->id_estudiante,
            'id_examen'              => $this->examen->id_examen,
            'id_asignacion_ambiente' => $this->asignacionAmbiente->id_asignacion_ambiente,
            'id_usuario'             => $this->controlUser->id_usuario,
            'motivo'                 => 'ESTUDIANTE_NO_HABILITADO',
        ]);
    }

    /**
     * Caso 5 (Reingreso no permitido - ING-02):
     * Si ya se registró el ingreso físico del estudiante para la habilitación, un segundo intento
     * retorna HTTP 409 Conflict y registra en intento_ingreso.
     */
    public function test_reingreso_duplicado_retorna_409_reingreso_no_permitido(): void
    {
        // Primer ingreso exitoso
        $this->withHeaders([
            'Authorization' => "Bearer {$this->controlToken}",
            'Accept'        => 'application/json',
        ])->postJson('/api/ingresos', [
            'id_examen'              => $this->examen->id_examen,
            'id_habilitacion'        => $this->habilitacionValida->id_habilitacion,
            'id_asignacion_ambiente' => $this->asignacionAmbiente->id_asignacion_ambiente,
        ])->assertStatus(201);

        $this->assertSame(1, Ingreso::count());

        // Segundo intento de ingreso con la misma credencial/habilitación
        $responseSegundoIntento = $this->withHeaders([
            'Authorization' => "Bearer {$this->controlToken}",
            'Accept'        => 'application/json',
        ])->postJson('/api/ingresos', [
            'id_examen'              => $this->examen->id_examen,
            'id_habilitacion'        => $this->habilitacionValida->id_habilitacion,
            'id_asignacion_ambiente' => $this->asignacionAmbiente->id_asignacion_ambiente,
        ]);

        $responseSegundoIntento->assertStatus(409)
            ->assertJson([
                'message'  => 'El estudiante ya registró su ingreso a este examen.',
                'code'     => 'REINGRESO_NO_PERMITIDO',
                'error'    => 'REINGRESO_NO_PERMITIDO',
                'ambiente' => 'Aula 624',
            ])
            ->assertJsonStructure([
                'message',
                'code',
                'error',
                'primer_ingreso',
                'fecha_hora_primer_ingreso',
                'ambiente',
            ]);

        // Se mantiene exactamente 1 ingreso
        $this->assertSame(1, Ingreso::count());

        // Debe haberse registrado el intento de reingreso fraudulento en intento_ingreso
        $this->assertDatabaseHas('intento_ingreso', [
            'id_estudiante'          => $this->estudianteHabilitado->id_estudiante,
            'id_examen'              => $this->examen->id_examen,
            'id_asignacion_ambiente' => $this->asignacionAmbiente->id_asignacion_ambiente,
            'id_usuario'             => $this->controlUser->id_usuario,
            'motivo'                 => 'REINGRESO_DUPLICADO',
        ]);

        // Debe haberse registrado la auditoría del intento de reingreso
        $this->assertDatabaseHas('audit_logs', [
            'accion'     => 'INTENTO_REINGRESO_DUPLICADO',
            'entidad'    => 'ingreso',
            'id_usuario' => $this->controlUser->id_usuario,
        ]);
    }

    /**
     * Caso 6: Si la base de datos detecta una colisión de clave única por id_habilitacion,
     * el sistema devuelve HTTP 409 REINGRESO_NO_PERMITIDO con el ambiente y hora previa, nunca un HTTP 500.
     */
    public function test_colision_unique_retorna_409_en_vez_de_500(): void
    {
        // Creamos directamente el primer ingreso en base de datos
        $primerIngreso = Ingreso::create([
            'id_examen'              => $this->examen->id_examen,
            'id_habilitacion'        => $this->habilitacionValida->id_habilitacion,
            'id_asignacion_ambiente' => $this->asignacionAmbiente->id_asignacion_ambiente,
            'id_usuario'             => $this->controlUser->id_usuario,
            'fecha_hora'             => now()->subMinutes(10),
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->controlToken}",
            'Accept'        => 'application/json',
        ])->postJson('/api/ingresos', [
            'id_examen'              => $this->examen->id_examen,
            'id_habilitacion'        => $this->habilitacionValida->id_habilitacion,
            'id_asignacion_ambiente' => $this->asignacionAmbiente->id_asignacion_ambiente,
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'message'                   => 'El estudiante ya registró su ingreso a este examen.',
                'code'                      => 'REINGRESO_NO_PERMITIDO',
                'error'                     => 'REINGRESO_NO_PERMITIDO',
                'primer_ingreso'            => $primerIngreso->fecha_hora->format('H:i:s'),
                'fecha_hora_primer_ingreso' => $primerIngreso->fecha_hora->toIso8601String(),
                'ambiente'                  => 'Aula 624',
            ]);
    }

    /**
     * Caso 7 (Atomicidad): Si falla el registro de auditoría dentro de la transacción de POST /api/ingresos,
     * se ejecuta rollback completo y no queda guardado ni el ingreso ni registros parciales de auditoría.
     */
    public function test_fallo_al_registrar_auditoria_revierte_el_ingreso_por_atomicidad(): void
    {
        $auditLogsIniciales = AuditLog::count();
        $simularFallo = true;

        AuditLog::creating(function (AuditLog $log) use (&$simularFallo): void {
            if ($simularFallo && $log->accion === AccionAuditoria::REGISTRO_INGRESO_EXAMEN->value) {
                throw new \RuntimeException('Fallo simulado en auditoría transaccional.');
            }
        });

        try {
            $response = $this->withHeaders([
                'Authorization' => "Bearer {$this->controlToken}",
                'Accept'        => 'application/json',
            ])->postJson('/api/ingresos', [
                'id_examen'              => $this->examen->id_examen,
                'id_habilitacion'        => $this->habilitacionValida->id_habilitacion,
                'id_asignacion_ambiente' => $this->asignacionAmbiente->id_asignacion_ambiente,
            ]);

            $response->assertStatus(500);
        } finally {
            $simularFallo = false;
        }

        // Debe ejecutarse rollback total: 0 ingresos y ningún nuevo audit_log de ingreso
        $this->assertSame(0, Ingreso::count());
        $this->assertSame($auditLogsIniciales, AuditLog::count());
    }
}
