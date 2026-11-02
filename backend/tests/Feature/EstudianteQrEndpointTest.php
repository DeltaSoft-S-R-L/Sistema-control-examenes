<?php

namespace Tests\Feature;

use App\Models\Carrera;
use App\Models\Estudiante;
use App\Models\Facultad;
use App\Models\Rol;
use App\Models\Usuario;
use App\Utilities\PasswordHasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EstudianteQrEndpointTest extends TestCase
{
    use RefreshDatabase;

    private Rol $rolAdmin;
    private Rol $rolDocente;
    private Rol $rolControl;
    private Usuario $adminUser;
    private Usuario $docenteUser;
    private Usuario $controlUser;
    private Estudiante $estudiante;
    private string $adminToken;
    private string $docenteToken;
    private string $controlToken;

    protected function setUp(): void
    {
        parent::setUp();

        // Roles del sistema
        $this->rolAdmin = Rol::create([
            'nombre'      => 'ADMINISTRADOR',
            'descripcion' => 'Administrador del sistema',
        ]);

        $this->rolDocente = Rol::create([
            'nombre'      => 'DOCENTE',
            'descripcion' => 'Docente del sistema',
        ]);

        $this->rolControl = Rol::create([
            'nombre'      => 'CONTROL_INGRESO',
            'descripcion' => 'Personal de control de ingreso',
        ]);

        // Usuarios
        $this->adminUser = Usuario::create([
            'nombre'        => 'Admin',
            'apellido'      => 'General',
            'correo'        => 'admin@umss.edu.bo',
            'username'      => 'admin_qr',
            'password_hash' => PasswordHasher::hash('Admin123'),
            'id_rol'        => $this->rolAdmin->id_rol,
            'estado'        => 'ACTIVO',
        ]);
        $this->adminToken = $this->adminUser->createToken('admin-token')->plainTextToken;

        $this->docenteUser = Usuario::create([
            'nombre'        => 'Docente',
            'apellido'      => 'Prueba',
            'correo'        => 'docente@umss.edu.bo',
            'username'      => 'docente_qr',
            'password_hash' => PasswordHasher::hash('Docente123'),
            'id_rol'        => $this->rolDocente->id_rol,
            'estado'        => 'ACTIVO',
        ]);
        $this->docenteToken = $this->docenteUser->createToken('docente-token')->plainTextToken;

        $this->controlUser = Usuario::create([
            'nombre'        => 'Control',
            'apellido'      => 'Puerta',
            'correo'        => 'control@umss.edu.bo',
            'username'      => 'control_qr',
            'password_hash' => PasswordHasher::hash('Control123'),
            'id_rol'        => $this->rolControl->id_rol,
            'estado'        => 'ACTIVO',
        ]);
        $this->controlToken = $this->controlUser->createToken('control-token')->plainTextToken;

        // Estructura Académica y Estudiante
        $facultad = Facultad::create([
            'codigo' => 'FCYT',
            'nombre' => 'Facultad de Ciencias y Tecnología',
        ]);

        $carrera = Carrera::create([
            'codigo'      => 'SIS',
            'nombre'      => 'Ingeniería de Sistemas',
            'id_facultad' => $facultad->id_facultad,
        ]);

        $this->estudiante = Estudiante::create([
            'ci'                   => '98765432',
            'nombre'               => 'Ana',
            'apellido'             => 'Torres Mendoza',
            'codigo_universitario' => '202309999',
            'id_carrera'           => $carrera->id_carrera,
            'correo'               => 'ana.torres@umss.edu.bo',
            'estado'               => 'ACTIVO',
        ]);
    }

    /**
     * 1. Admin autenticado solicita GET /api/estudiantes/{id}/qr
     * -> recibe 200 OK, Content-Type: image/svg+xml y cuerpo con <svg.
     */
    public function test_admin_autenticado_puede_obtener_qr_de_estudiante(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->adminToken}",
            'Accept'        => 'image/svg+xml, application/json',
        ])->get("/api/estudiantes/{$this->estudiante->id_estudiante}/qr");

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/svg+xml');
        $this->assertStringContainsString(
            "qr-estudiante-{$this->estudiante->id_estudiante}.svg",
            $response->headers->get('Content-Disposition')
        );

        $svgContent = $response->getContent();
        $this->assertStringContainsString('<svg', $svgContent);
        $this->assertStringContainsString('</svg>', $svgContent);
    }

    /**
     * 2. Usuario con rol distinto a Admin (Docente o Control)
     * -> recibe 403 Forbidden.
     */
    public function test_docente_no_puede_obtener_qr_recibe_403(): void
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

    public function test_control_ingreso_no_puede_obtener_qr_recibe_403(): void
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

    /**
     * 3. Usuario no autenticado -> recibe 401 Unauthorized.
     */
    public function test_usuario_no_autenticado_recibe_401(): void
    {
        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->get("/api/estudiantes/{$this->estudiante->id_estudiante}/qr");

        $response->assertStatus(401);
    }

    /**
     * 4. Estudiante inexistente -> recibe 404 Not Found.
     */
    public function test_estudiante_inexistente_retorna_404(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->adminToken}",
            'Accept'        => 'application/json',
        ])->get('/api/estudiantes/999999/qr');

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Estudiante no encontrado.',
            ]);
    }
}
