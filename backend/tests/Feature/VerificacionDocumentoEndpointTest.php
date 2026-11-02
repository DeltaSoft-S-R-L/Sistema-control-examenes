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

class VerificacionDocumentoEndpointTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $controlUser;
    private string $controlToken;
    private Estudiante $estudiante;
    private Examen $examen;
    private QrTokenService $qrService;

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

        // Estudiante base
        $this->estudiante = Estudiante::create([
            'ci'                   => '7654321',
            'nombre'               => 'Mateo',
            'apellido'             => 'Vargas Claros',
            'codigo_universitario' => '202105555',
            'id_carrera'           => $carrera->id_carrera,
            'correo'               => 'mateo.vargas@umss.edu.bo',
            'estado'               => 'ACTIVO',
        ]);

        // Examen
        $this->examen = Examen::create([
            'id_asignatura'    => $asignatura->id_asignatura,
            'nombre'           => 'Segundo Parcial - Ing. Software',
            'fecha'            => '2026-11-20',
            'hora_inicio'      => '10:00:00',
            'duracion_minutos' => 90,
            'descripcion'      => 'Evaluación parcial presencial',
            'estado'           => 'PROGRAMADO',
        ]);
    }

    /**
     * 1. Verificación exitosa por CI (200 OK)
     */
    public function test_verificacion_por_ci_exitosa(): void
    {
        $conteoIngresosPrevio = Ingreso::count();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->controlToken}",
            'Accept'        => 'application/json',
        ])->postJson('/api/verificaciones/documento', [
            'tipo'      => 'CI',
            'valor'     => '7654321',
            'examen_id' => $this->examen->id_examen,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'metodo' => 'CI',
                'estudiante' => [
                    'id'         => $this->estudiante->id_estudiante,
                    'nombres'    => 'Mateo',
                    'apellidos'  => 'Vargas Claros',
                    'ci'         => '7654321',
                    'codigo_sis' => '202105555',
                ],
                'examen' => [
                    'id'         => $this->examen->id_examen,
                    'asignatura' => 'INGENIERIA DE SOFTWARE',
                    'fecha'      => '2026-11-20',
                    'hora'       => '10:00:00',
                ],
            ]);

        // Criterio de No-Persistencia: No debe tocar tablas de dominio
        $this->assertSame($conteoIngresosPrevio, Ingreso::count());
    }

    /**
     * 2. Verificación exitosa por SIS (200 OK)
     */
    public function test_verificacion_por_sis_exitosa(): void
    {
        $conteoIngresosPrevio = Ingreso::count();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->controlToken}",
            'Accept'        => 'application/json',
        ])->postJson('/api/verificaciones/documento', [
            'tipo'      => 'SIS',
            'valor'     => '202105555',
            'examen_id' => $this->examen->id_examen,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'metodo' => 'SIS',
                'estudiante' => [
                    'id'         => $this->estudiante->id_estudiante,
                    'nombres'    => 'Mateo',
                    'apellidos'  => 'Vargas Claros',
                    'ci'         => '7654321',
                    'codigo_sis' => '202105555',
                ],
                'examen' => [
                    'id'         => $this->examen->id_examen,
                    'asignatura' => 'INGENIERIA DE SOFTWARE',
                    'fecha'      => '2026-11-20',
                    'hora'       => '10:00:00',
                ],
            ]);

        $this->assertSame($conteoIngresosPrevio, Ingreso::count());
    }

    /**
     * 3. Normalización de valor con complemento y espacios (200 OK)
     * Crea un estudiante con CI "84920191B" y envía "  8492019 1b  ".
     */
    public function test_normalizacion_de_valor_con_complemento_y_espacios(): void
    {
        $estudianteComplemento = Estudiante::create([
            'ci'                   => '84920191B',
            'nombre'               => 'Lucia',
            'apellido'             => 'Morales Rios',
            'codigo_universitario' => '202208888',
            'id_carrera'           => $this->estudiante->id_carrera,
            'correo'               => 'lucia.morales@umss.edu.bo',
            'estado'               => 'ACTIVO',
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->controlToken}",
            'Accept'        => 'application/json',
        ])->postJson('/api/verificaciones/documento', [
            'tipo'      => 'ci', // minúsculas para probar normalización del tipo
            'valor'     => '  8492019 1b  ', // espacios externos, internos y minúsculas
            'examen_id' => $this->examen->id_examen,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'metodo' => 'CI',
                'estudiante' => [
                    'id'         => $estudianteComplemento->id_estudiante,
                    'nombres'    => 'Lucia',
                    'apellidos'  => 'Morales Rios',
                    'ci'         => '84920191B',
                    'codigo_sis' => '202208888',
                ],
                'examen' => [
                    'id' => $this->examen->id_examen,
                ],
            ]);
    }

    /**
     * 4. Estudiante inexistente retorna 404 ESTUDIANTE_NO_ENCONTRADO.
     */
    public function test_estudiante_inexistente_retorna_404_estudiante_no_encontrado(): void
    {
        // Prueba con CI inexistente
        $responseCi = $this->withHeaders([
            'Authorization' => "Bearer {$this->controlToken}",
            'Accept'        => 'application/json',
        ])->postJson('/api/verificaciones/documento', [
            'tipo'      => 'CI',
            'valor'     => '999999999',
            'examen_id' => $this->examen->id_examen,
        ]);

        $responseCi->assertStatus(404)
            ->assertJson([
                'code'  => 'ESTUDIANTE_NO_ENCONTRADO',
                'error' => 'ESTUDIANTE_NO_ENCONTRADO',
            ]);

        // Prueba con SIS inexistente
        $responseSis = $this->withHeaders([
            'Authorization' => "Bearer {$this->controlToken}",
            'Accept'        => 'application/json',
        ])->postJson('/api/verificaciones/documento', [
            'tipo'      => 'SIS',
            'valor'     => '199999999',
            'examen_id' => $this->examen->id_examen,
        ]);

        $responseSis->assertStatus(404)
            ->assertJson([
                'code'  => 'ESTUDIANTE_NO_ENCONTRADO',
                'error' => 'ESTUDIANTE_NO_ENCONTRADO',
            ]);
    }

    /**
     * 5. Examen inexistente retorna 404 EXAMEN_NO_ENCONTRADO.
     */
    public function test_examen_inexistente_retorna_404_examen_no_encontrado(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->controlToken}",
            'Accept'        => 'application/json',
        ])->postJson('/api/verificaciones/documento', [
            'tipo'      => 'CI',
            'valor'     => '7654321',
            'examen_id' => 999999,
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'code'  => 'EXAMEN_NO_ENCONTRADO',
                'error' => 'EXAMEN_NO_ENCONTRADO',
            ]);
    }

    /**
     * 6. Validación de tipo o valor inválido retorna HTTP 422.
     */
    public function test_validacion_de_tipo_o_valor_invalido_retorna_422(): void
    {
        // 6a. Tipo no permitido ('PASAPORTE')
        $respTipoInvalido = $this->withHeaders([
            'Authorization' => "Bearer {$this->controlToken}",
            'Accept'        => 'application/json',
        ])->postJson('/api/verificaciones/documento', [
            'tipo'      => 'PASAPORTE',
            'valor'     => '7654321',
            'examen_id' => $this->examen->id_examen,
        ]);

        $respTipoInvalido->assertStatus(422)
            ->assertJson([
                'code' => 'VALIDACION_FALLIDA',
            ]);

        // 6b. Valor compuesto únicamente de espacios en blanco
        $respValorVacio = $this->withHeaders([
            'Authorization' => "Bearer {$this->controlToken}",
            'Accept'        => 'application/json',
        ])->postJson('/api/verificaciones/documento', [
            'tipo'      => 'CI',
            'valor'     => '     ',
            'examen_id' => $this->examen->id_examen,
        ]);

        $respValorVacio->assertStatus(422)
            ->assertJson([
                'code' => 'VALIDACION_FALLIDA',
            ]);

        // 6c. Ausencia de examen_id
        $respSinExamen = $this->withHeaders([
            'Authorization' => "Bearer {$this->controlToken}",
            'Accept'        => 'application/json',
        ])->postJson('/api/verificaciones/documento', [
            'tipo'  => 'CI',
            'valor' => '7654321',
        ]);

        $respSinExamen->assertStatus(422)
            ->assertJson([
                'code' => 'VALIDACION_FALLIDA',
            ]);
    }

    /**
     * 7. Consistencia de contrato con QR:
     * Comprobar que las llaves JSON devueltas por documento y qr sean idénticas.
     */
    public function test_consistencia_de_contrato_con_qr(): void
    {
        $qrToken = $this->qrService->generar($this->estudiante);

        // Petición QR
        $respQr = $this->withHeaders([
            'Authorization' => "Bearer {$this->controlToken}",
            'Accept'        => 'application/json',
        ])->postJson('/api/verificaciones/qr', [
            'token'     => $qrToken,
            'examen_id' => $this->examen->id_examen,
        ]);
        $respQr->assertStatus(200);

        // Petición Documento
        $respDoc = $this->withHeaders([
            'Authorization' => "Bearer {$this->controlToken}",
            'Accept'        => 'application/json',
        ])->postJson('/api/verificaciones/documento', [
            'tipo'      => 'CI',
            'valor'     => $this->estudiante->ci,
            'examen_id' => $this->examen->id_examen,
        ]);
        $respDoc->assertStatus(200);

        $jsonQr = $respQr->json();
        $jsonDoc = $respDoc->json();

        // 1. Mismas llaves en la raíz: ['metodo', 'estudiante', 'examen']
        $this->assertEquals(array_keys($jsonQr), array_keys($jsonDoc));
        $this->assertEquals(['metodo', 'estudiante', 'examen'], array_keys($jsonDoc));

        // 2. Mismas llaves en el objeto estudiante: ['id', 'nombres', 'apellidos', 'ci', 'codigo_sis']
        $this->assertEquals(array_keys($jsonQr['estudiante']), array_keys($jsonDoc['estudiante']));
        $this->assertEquals(['id', 'nombres', 'apellidos', 'ci', 'codigo_sis'], array_keys($jsonDoc['estudiante']));

        // 3. Mismas llaves en el objeto examen: ['id', 'asignatura', 'fecha', 'hora']
        $this->assertEquals(array_keys($jsonQr['examen']), array_keys($jsonDoc['examen']));
        $this->assertEquals(['id', 'asignatura', 'fecha', 'hora'], array_keys($jsonDoc['examen']));
    }

    /**
     * 8. Petición no autenticada retorna 401 Unauthorized.
     */
    public function test_peticion_sin_autenticacion_retorna_401(): void
    {
        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->postJson('/api/verificaciones/documento', [
            'tipo'      => 'CI',
            'valor'     => '7654321',
            'examen_id' => $this->examen->id_examen,
        ]);

        $response->assertStatus(401);
    }
}
