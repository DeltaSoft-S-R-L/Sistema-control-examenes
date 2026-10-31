<?php

namespace Tests\Feature;

use App\Models\Asignatura;
use App\Models\Carrera;
use App\Models\Estudiante;
use App\Models\Examen;
use App\Models\Facultad;
use App\Models\Rol;
use App\Models\Usuario;
use App\Services\QrTokenService;
use App\Utilities\PasswordHasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerificacionRbacMatrixTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $adminUser;
    private string $adminToken;

    private Usuario $docenteUser;
    private string $docenteToken;

    private Usuario $controlUser;
    private string $controlToken;

    private Usuario $inactivoUser;
    private string $inactivoToken;

    private Estudiante $estudiante;
    private Examen $examen;
    private string $validQrToken;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Roles
        $rolAdmin = Rol::create([
            'nombre'      => 'ADMINISTRADOR',
            'descripcion' => 'Administrador del sistema',
        ]);

        $rolDocente = Rol::create([
            'nombre'      => 'DOCENTE',
            'descripcion' => 'Docente universitario',
        ]);

        $rolControl = Rol::create([
            'nombre'      => 'CONTROL_INGRESO',
            'descripcion' => 'Personal de control de ingreso',
        ]);

        // 2. Usuarios con roles
        $this->adminUser = Usuario::create([
            'nombre'        => 'Admin',
            'apellido'      => 'Principal',
            'correo'        => 'admin@umss.edu.bo',
            'username'      => 'admin_test',
            'password_hash' => PasswordHasher::hash('Admin123'),
            'id_rol'        => $rolAdmin->id_rol,
            'estado'        => 'ACTIVO',
        ]);
        $this->adminToken = $this->adminUser->createToken('admin-token')->plainTextToken;

        $this->docenteUser = Usuario::create([
            'nombre'        => 'Docente',
            'apellido'      => 'Titular',
            'correo'        => 'docente@umss.edu.bo',
            'username'      => 'docente_test',
            'password_hash' => PasswordHasher::hash('Docente123'),
            'id_rol'        => $rolDocente->id_rol,
            'estado'        => 'ACTIVO',
        ]);
        $this->docenteToken = $this->docenteUser->createToken('docente-token')->plainTextToken;

        $this->controlUser = Usuario::create([
            'nombre'        => 'Control',
            'apellido'      => 'Ingreso',
            'correo'        => 'control@umss.edu.bo',
            'username'      => 'control_test',
            'password_hash' => PasswordHasher::hash('Control123'),
            'id_rol'        => $rolControl->id_rol,
            'estado'        => 'ACTIVO',
        ]);
        $this->controlToken = $this->controlUser->createToken('control-token')->plainTextToken;

        // Usuario inactivo para validar USR-03
        $this->inactivoUser = Usuario::create([
            'nombre'        => 'Revocado',
            'apellido'      => 'Usuario',
            'correo'        => 'revocado@umss.edu.bo',
            'username'      => 'revocado_test',
            'password_hash' => PasswordHasher::hash('Revocado123'),
            'id_rol'        => $rolControl->id_rol,
            'estado'        => 'INACTIVO',
        ]);
        $this->inactivoToken = $this->inactivoUser->createToken('inactivo-token')->plainTextToken;

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
            'codigo'      => '2010020',
            'nombre'      => 'INGENIERIA DE SOFTWARE',
            'descripcion' => 'Nivel F - Sistemas',
        ]);

        // 4. Estudiante
        $this->estudiante = Estudiante::create([
            'ci'                   => '4589210',
            'nombre'               => 'Santiago',
            'apellido'             => 'Paz Arispe',
            'codigo_universitario' => '202001111',
            'id_carrera'           => $carrera->id_carrera,
            'correo'               => 'santiago.paz@umss.edu.bo',
            'estado'               => 'ACTIVO',
        ]);

        // 5. Examen
        $this->examen = Examen::create([
            'id_asignatura'    => $asignatura->id_asignatura,
            'nombre'           => 'Examen Final - Ing. Software',
            'fecha'            => '2026-11-25',
            'hora_inicio'      => '09:00:00',
            'duracion_minutos' => 120,
            'descripcion'      => 'Examen final presencial',
            'estado'           => 'PROGRAMADO',
        ]);

        // 6. Token QR firmado
        $qrService = app(QrTokenService::class);
        $this->validQrToken = $qrService->generar($this->estudiante);
    }

    /*
    |--------------------------------------------------------------------------
    | Matriz Fila 1: GET /api/estudiantes/{id}/qr (Solo ADMINISTRADOR)
    |--------------------------------------------------------------------------
    */

    public function test_estudiante_qr_sin_sesion_retorna_401(): void
    {
        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get("/api/estudiantes/{$this->estudiante->id_estudiante}/qr");

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'No autenticado.',
            ]);
    }

    public function test_estudiante_qr_rol_docente_retorna_403(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->docenteToken}",
            'Accept'        => 'application/json',
        ])->get("/api/estudiantes/{$this->estudiante->id_estudiante}/qr");

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'No tiene permisos para realizar esta acción.',
            ]);
    }

    public function test_estudiante_qr_rol_control_retorna_403(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->controlToken}",
            'Accept'        => 'application/json',
        ])->get("/api/estudiantes/{$this->estudiante->id_estudiante}/qr");

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'No tiene permisos para realizar esta acción.',
            ]);
    }

    public function test_estudiante_qr_rol_admin_retorna_200(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->adminToken}",
            'Accept'        => 'application/json',
        ])->get("/api/estudiantes/{$this->estudiante->id_estudiante}/qr");

        $response->assertStatus(200);
        $this->assertSame('image/svg+xml', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('<svg', $response->getContent());
    }

    /*
    |--------------------------------------------------------------------------
    | Matriz Fila 2: POST /api/verificaciones/qr (CONTROL_INGRESO y ADMINISTRADOR)
    |--------------------------------------------------------------------------
    */

    public function test_verificaciones_qr_sin_sesion_retorna_401(): void
    {
        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->postJson('/api/verificaciones/qr', [
            'token'     => $this->validQrToken,
            'examen_id' => $this->examen->id_examen,
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'No autenticado.',
            ]);
    }

    public function test_verificaciones_qr_rol_docente_retorna_403(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->docenteToken}",
            'Accept'        => 'application/json',
        ])->postJson('/api/verificaciones/qr', [
            'token'     => $this->validQrToken,
            'examen_id' => $this->examen->id_examen,
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'No tiene permisos para realizar esta acción.',
            ]);
    }

    public function test_verificaciones_qr_rol_control_retorna_200(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->controlToken}",
            'Accept'        => 'application/json',
        ])->postJson('/api/verificaciones/qr', [
            'token'     => $this->validQrToken,
            'examen_id' => $this->examen->id_examen,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'metodo' => 'QR',
                'estudiante' => [
                    'id' => $this->estudiante->id_estudiante,
                    'ci' => '4589210',
                ],
                'examen' => [
                    'id' => $this->examen->id_examen,
                ],
            ]);
    }

    public function test_verificaciones_qr_rol_admin_retorna_200(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->adminToken}",
            'Accept'        => 'application/json',
        ])->postJson('/api/verificaciones/qr', [
            'token'     => $this->validQrToken,
            'examen_id' => $this->examen->id_examen,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'metodo' => 'QR',
                'estudiante' => [
                    'id' => $this->estudiante->id_estudiante,
                    'ci' => '4589210',
                ],
                'examen' => [
                    'id' => $this->examen->id_examen,
                ],
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Matriz Fila 3: POST /api/verificaciones/documento (CONTROL_INGRESO y ADMINISTRADOR)
    |--------------------------------------------------------------------------
    */

    public function test_verificaciones_documento_sin_sesion_retorna_401(): void
    {
        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->postJson('/api/verificaciones/documento', [
            'tipo'      => 'CI',
            'valor'     => '4589210',
            'examen_id' => $this->examen->id_examen,
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'No autenticado.',
            ]);
    }

    public function test_verificaciones_documento_rol_docente_retorna_403(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->docenteToken}",
            'Accept'        => 'application/json',
        ])->postJson('/api/verificaciones/documento', [
            'tipo'      => 'CI',
            'valor'     => '4589210',
            'examen_id' => $this->examen->id_examen,
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'No tiene permisos para realizar esta acción.',
            ]);
    }

    public function test_verificaciones_documento_rol_control_retorna_200(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->controlToken}",
            'Accept'        => 'application/json',
        ])->postJson('/api/verificaciones/documento', [
            'tipo'      => 'CI',
            'valor'     => '4589210',
            'examen_id' => $this->examen->id_examen,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'metodo' => 'CI',
                'estudiante' => [
                    'id' => $this->estudiante->id_estudiante,
                    'ci' => '4589210',
                ],
                'examen' => [
                    'id' => $this->examen->id_examen,
                ],
            ]);
    }

    public function test_verificaciones_documento_rol_admin_retorna_200(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->adminToken}",
            'Accept'        => 'application/json',
        ])->postJson('/api/verificaciones/documento', [
            'tipo'      => 'SIS',
            'valor'     => '202001111',
            'examen_id' => $this->examen->id_examen,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'metodo' => 'SIS',
                'estudiante' => [
                    'id'         => $this->estudiante->id_estudiante,
                    'codigo_sis' => '202001111',
                ],
                'examen' => [
                    'id' => $this->examen->id_examen,
                ],
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Control de Estado Inactivo / Revocado (USR-03)
    |--------------------------------------------------------------------------
    */

    public function test_usuario_inactivo_bloqueado_en_todas_las_rutas_con_401(): void
    {
        // 1. GET /api/estudiantes/{id}/qr
        $res1 = $this->withHeaders([
            'Authorization' => "Bearer {$this->inactivoToken}",
            'Accept'        => 'application/json',
        ])->get("/api/estudiantes/{$this->estudiante->id_estudiante}/qr");

        $res1->assertStatus(401)
            ->assertJson([
                'message' => 'Su cuenta se encuentra inactiva o revocada. Contacte al administrador.',
            ]);

        // 2. POST /api/verificaciones/qr
        $res2 = $this->withHeaders([
            'Authorization' => "Bearer {$this->inactivoToken}",
            'Accept'        => 'application/json',
        ])->postJson('/api/verificaciones/qr', [
            'token'     => $this->validQrToken,
            'examen_id' => $this->examen->id_examen,
        ]);

        $res2->assertStatus(401)
            ->assertJson([
                'message' => 'Su cuenta se encuentra inactiva o revocada. Contacte al administrador.',
            ]);

        // 3. POST /api/verificaciones/documento
        $res3 = $this->withHeaders([
            'Authorization' => "Bearer {$this->inactivoToken}",
            'Accept'        => 'application/json',
        ])->postJson('/api/verificaciones/documento', [
            'tipo'      => 'CI',
            'valor'     => '4589210',
            'examen_id' => $this->examen->id_examen,
        ]);

        $res3->assertStatus(401)
            ->assertJson([
                'message' => 'Su cuenta se encuentra inactiva o revocada. Contacte al administrador.',
            ]);
    }
}
