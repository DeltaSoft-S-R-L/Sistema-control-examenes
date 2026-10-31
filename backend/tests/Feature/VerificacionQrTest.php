<?php

namespace Tests\Feature;

use App\Models\Asignatura;
use App\Models\Carrera;
use App\Models\Estudiante;
use App\Models\Examen;
use App\Models\Facultad;
use App\Models\Ingreso;
use App\Models\Rol;
use App\Models\Usuario;
use App\Services\QrTokenService;
use App\Utilities\PasswordHasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerificacionQrTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $controlUser;
    private string $controlToken;
    private Estudiante $estudiante;
    private Examen $examen;
    private QrTokenService $qrService;
    private string $validToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->qrService = app(QrTokenService::class);

        // Rol de personal de control de ingreso
        $rolControl = Rol::create([
            'nombre'      => 'CONTROL_INGRESO',
            'descripcion' => 'Personal de control de ingreso a exámenes',
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
        $this->controlToken = $this->controlUser->createToken('test-control-token')->plainTextToken;

        // Estructura Académica
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

        // Estudiante
        $this->estudiante = Estudiante::create([
            'ci'                   => '98765432',
            'nombre'               => 'Ana',
            'apellido'             => 'Torres Mendoza',
            'codigo_universitario' => '202309999',
            'id_carrera'           => $carrera->id_carrera,
            'correo'               => 'ana.torres@umss.edu.bo',
            'estado'               => 'ACTIVO',
        ]);

        // Examen
        $this->examen = Examen::create([
            'id_asignatura'    => $asignatura->id_asignatura,
            'nombre'           => 'Primer Parcial - Ing. Software',
            'fecha'            => '2026-11-15',
            'hora_inicio'      => '08:30:00',
            'duracion_minutos' => 90,
            'descripcion'      => 'Evaluación parcial presencial',
            'estado'           => 'PROGRAMADO',
        ]);

        // Token QR válido para el estudiante
        $this->validToken = $this->qrService->generar($this->estudiante);
    }

    /**
     * 1. Verificación exitosa: Retorna 200 con la estructura común de VerificacionResource
     * y sin persistir registros en tablas de dominio.
     */
    public function test_verificacion_por_qr_exitosa(): void
    {
        $conteoIngresosPrevio = Ingreso::count();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->controlToken}",
            'Accept'        => 'application/json',
        ])->postJson('/api/verificaciones/qr', [
            'token'     => $this->validToken,
            'examen_id' => $this->examen->id_examen,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'metodo' => 'QR',
                'estudiante' => [
                    'id'         => $this->estudiante->id_estudiante,
                    'nombres'    => 'Ana',
                    'apellidos'  => 'Torres Mendoza',
                    'ci'         => '98765432',
                    'codigo_sis' => '202309999',
                ],
                'examen' => [
                    'id'         => $this->examen->id_examen,
                    'asignatura' => 'INGENIERIA DE SOFTWARE',
                    'fecha'      => '2026-11-15',
                    'hora'       => '08:30:00',
                ],
            ]);

        // Criterio de No-Persistencia: No debe registrar ingresos ni tocar tablas de dominio
        $this->assertSame($conteoIngresosPrevio, Ingreso::count());
    }

    /**
     * 2. Token alterado o con firma inválida retorna 422 con código QR_INVALIDO.
     */
    public function test_rechaza_qr_alterado_con_422_qr_invalido(): void
    {
        [$payloadB64, $signatureB64] = explode('.', $this->validToken);
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
                'code'  => 'QR_INVALIDO',
                'error' => 'QR_INVALIDO',
            ]);
    }

    /**
     * 3. Token malformado o vacío retorna 422 con código QR_INVALIDO.
     */
    public function test_rechaza_token_malformado_con_422_qr_invalido(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->controlToken}",
            'Accept'        => 'application/json',
        ])->postJson('/api/verificaciones/qr', [
            'token'     => 'cadena_sin_formato_punto',
            'examen_id' => $this->examen->id_examen,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'code'  => 'QR_INVALIDO',
                'error' => 'QR_INVALIDO',
            ]);
    }

    public function test_rechaza_token_vacio_con_422_qr_invalido(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->controlToken}",
            'Accept'        => 'application/json',
        ])->postJson('/api/verificaciones/qr', [
            'token'     => '',
            'examen_id' => $this->examen->id_examen,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'code'  => 'QR_INVALIDO',
                'error' => 'QR_INVALIDO',
            ]);
    }

    /**
     * 4. Token con versión no soportada (v=2) firmado válidamente retorna 422 QR_INVALIDO.
     */
    public function test_rechaza_token_con_version_desconocida_con_422(): void
    {
        $tokenV2 = $this->qrService->generarToken($this->estudiante->id_estudiante, 2);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->controlToken}",
            'Accept'        => 'application/json',
        ])->postJson('/api/verificaciones/qr', [
            'token'     => $tokenV2,
            'examen_id' => $this->examen->id_examen,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'code'  => 'QR_INVALIDO',
                'error' => 'QR_INVALIDO',
            ]);
    }

    /**
     * 5. Falta o invalidez de examen_id retorna 422 VALIDACION_FALLIDA.
     */
    public function test_rechaza_examen_id_faltante_con_422(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->controlToken}",
            'Accept'        => 'application/json',
        ])->postJson('/api/verificaciones/qr', [
            'token' => $this->validToken,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'code' => 'VALIDACION_FALLIDA',
            ]);
    }

    /**
     * 6. Estudiante no encontrado (token válido pero id_estudiante inexistente)
     * retorna 404 ESTUDIANTE_NO_ENCONTRADO.
     */
    public function test_estudiante_inexistente_retorna_404_estudiante_no_encontrado(): void
    {
        $tokenInexistente = $this->qrService->generarToken(999999);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->controlToken}",
            'Accept'        => 'application/json',
        ])->postJson('/api/verificaciones/qr', [
            'token'     => $tokenInexistente,
            'examen_id' => $this->examen->id_examen,
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'code'  => 'ESTUDIANTE_NO_ENCONTRADO',
                'error' => 'ESTUDIANTE_NO_ENCONTRADO',
            ]);
    }

    /**
     * 7. Examen no encontrado retorna 404 EXAMEN_NO_ENCONTRADO.
     */
    public function test_examen_inexistente_retorna_404_examen_no_encontrado(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->controlToken}",
            'Accept'        => 'application/json',
        ])->postJson('/api/verificaciones/qr', [
            'token'     => $this->validToken,
            'examen_id' => 888888,
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'code'  => 'EXAMEN_NO_ENCONTRADO',
                'error' => 'EXAMEN_NO_ENCONTRADO',
            ]);
    }

    /**
     * 8. Petición no autenticada retorna 401 Unauthorized.
     */
    public function test_peticion_sin_autenticacion_retorna_401(): void
    {
        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->postJson('/api/verificaciones/qr', [
            'token'     => $this->validToken,
            'examen_id' => $this->examen->id_examen,
        ]);

        $response->assertStatus(401);
    }
}
