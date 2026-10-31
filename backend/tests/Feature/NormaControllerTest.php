<?php

namespace Tests\Feature;

use App\Models\Asignatura;
use App\Models\Carrera;
use App\Models\Estudiante;
use App\Models\Examen;
use App\Models\Facultad;
use App\Models\Habilitacion;
use App\Models\ReglaExamen;
use App\Models\ReglaIndividual;
use App\Models\Rol;
use App\Models\Usuario;
use App\Utilities\PasswordHasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NormaControllerTest extends TestCase
{
    use RefreshDatabase;

    private Rol $rolAdmin;
    private Rol $rolDocente;
    private Rol $rolControl;
    private Usuario $userAdmin;
    private Usuario $userDocente;
    private Usuario $userControl;
    private string $tokenAdmin;
    private string $tokenDocente;
    private string $tokenControl;

    private Examen $examen;
    private Estudiante $estudiante;
    private Habilitacion $habilitacion;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Roles
        $this->rolAdmin = Rol::create(['nombre' => 'ADMINISTRADOR', 'descripcion' => 'Admin']);
        $this->rolDocente = Rol::create(['nombre' => 'DOCENTE', 'descripcion' => 'Docente']);
        $this->rolControl = Rol::create(['nombre' => 'CONTROL_INGRESO', 'descripcion' => 'Control']);

        // 2. Usuarios
        $this->userAdmin = Usuario::create([
            'nombre'        => 'Admin',
            'apellido'      => 'Sistema',
            'correo'        => 'admin@normas.local',
            'username'      => 'admin_normas',
            'password_hash' => PasswordHasher::hash('Password123'),
            'id_rol'        => $this->rolAdmin->id_rol,
            'estado'        => 'ACTIVO',
        ]);
        $this->tokenAdmin = $this->userAdmin->createToken('token-admin')->plainTextToken;

        $this->userDocente = Usuario::create([
            'nombre'        => 'Docente',
            'apellido'      => 'Prueba',
            'correo'        => 'docente@normas.local',
            'username'      => 'docente_normas',
            'password_hash' => PasswordHasher::hash('Password123'),
            'id_rol'        => $this->rolDocente->id_rol,
            'estado'        => 'ACTIVO',
        ]);
        $this->tokenDocente = $this->userDocente->createToken('token-docente')->plainTextToken;

        $this->userControl = Usuario::create([
            'nombre'        => 'Control',
            'apellido'      => 'Prueba',
            'correo'        => 'control@normas.local',
            'username'      => 'control_normas',
            'password_hash' => PasswordHasher::hash('Password123'),
            'id_rol'        => $this->rolControl->id_rol,
            'estado'        => 'ACTIVO',
        ]);
        $this->tokenControl = $this->userControl->createToken('token-control')->plainTextToken;

        // 3. Estructura Académica y Examen
        $facultad = Facultad::create(['codigo' => 'FCYT', 'nombre' => 'Facultad de Ciencias y Tecnología']);
        $carrera = Carrera::create([
            'id_facultad' => $facultad->id_facultad,
            'codigo'      => 'SIS',
            'nombre'      => 'Ingeniería de Sistemas',
        ]);
        $asignatura = Asignatura::create(['codigo' => 'SIS-101', 'nombre' => 'Programación I']);

        $this->examen = Examen::create([
            'id_asignatura'    => $asignatura->id_asignatura,
            'nombre'           => 'Examen Final Programación',
            'fecha'            => '2026-11-20',
            'hora_inicio'      => '08:00',
            'duracion_minutos' => 90,
            'estado'           => 'PROGRAMADO',
        ]);

        $this->estudiante = Estudiante::create([
            'ci'                   => '7654321',
            'nombre'               => 'Ana',
            'apellido'             => 'Torres',
            'codigo_universitario' => '202100555',
            'id_carrera'           => $carrera->id_carrera,
            'correo'               => 'ana.torres@universidad.edu',
            'estado'               => 'ACTIVO',
        ]);

        $this->habilitacion = Habilitacion::create([
            'id_estudiante' => $this->estudiante->id_estudiante,
            'id_examen'     => $this->examen->id_examen,
            'estado'        => 'HABILITADO',
            'motivo'        => null,
        ]);
    }

    public function test_admin_y_docente_pueden_crear_norma_general(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->tokenDocente}")
            ->postJson("/api/examenes/{$this->examen->id_examen}/normas", [
                'tipo'        => 'GENERAL',
                'descripcion' => 'Prohibido el uso de teléfonos celulares durante el examen.',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Norma general registrada correctamente.',
                'norma'   => [
                    'tipo'        => 'GENERAL',
                    'descripcion' => 'Prohibido el uso de teléfonos celulares durante el examen.',
                    'estado'      => 'ACTIVA',
                ],
            ]);

        $this->assertDatabaseHas('regla_examen', [
            'id_examen'   => $this->examen->id_examen,
            'descripcion' => 'Prohibido el uso de teléfonos celulares durante el examen.',
            'estado'      => 'ACTIVA',
        ]);
    }

    public function test_admin_y_docente_pueden_crear_norma_particular(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->tokenAdmin}")
            ->postJson("/api/examenes/{$this->examen->id_examen}/normas", [
                'tipo'            => 'PARTICULAR',
                'descripcion'     => 'Permitido el uso de computadora portátil con autorización médica.',
                'id_habilitacion' => $this->habilitacion->id_habilitacion,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Norma particular registrada correctamente.',
                'norma'   => [
                    'tipo'            => 'PARTICULAR',
                    'descripcion'     => 'Permitido el uso de computadora portátil con autorización médica.',
                    'id_habilitacion' => $this->habilitacion->id_habilitacion,
                    'estudiante'      => [
                        'ci' => '7654321',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('regla_individual', [
            'id_habilitacion' => $this->habilitacion->id_habilitacion,
            'descripcion'     => 'Permitido el uso de computadora portátil con autorización médica.',
            'estado'          => 'ACTIVA',
        ]);
    }

    public function test_valida_campos_obligatorios_y_longitud_maxima_1000(): void
    {
        // 1. Tipo faltante y descripcion vacia
        $responseVacio = $this->withHeader('Authorization', "Bearer {$this->tokenAdmin}")
            ->postJson("/api/examenes/{$this->examen->id_examen}/normas", [
                'tipo'        => '',
                'descripcion' => '',
            ]);

        $responseVacio->assertStatus(422)
            ->assertJsonValidationErrors(['tipo', 'descripcion']);

        // 2. Descripcion excede 1000 caracteres
        $responseLargo = $this->withHeader('Authorization', "Bearer {$this->tokenAdmin}")
            ->postJson("/api/examenes/{$this->examen->id_examen}/normas", [
                'tipo'        => 'GENERAL',
                'descripcion' => str_repeat('A', 1001),
            ]);

        $responseLargo->assertStatus(422)
            ->assertJsonValidationErrors(['descripcion']);

        // 3. Particular sin estudiante valido
        $responseSinEstudiante = $this->withHeader('Authorization', "Bearer {$this->tokenAdmin}")
            ->postJson("/api/examenes/{$this->examen->id_examen}/normas", [
                'tipo'        => 'PARTICULAR',
                'descripcion' => 'Uso de calculadora',
            ]);

        $responseSinEstudiante->assertStatus(422)
            ->assertJsonValidationErrors(['id_estudiante']);
    }

    public function test_rol_control_no_puede_crear_ni_eliminar_normas_retorna_403(): void
    {
        // Intentar crear -> 403
        $resCrear = $this->withHeader('Authorization', "Bearer {$this->tokenControl}")
            ->postJson("/api/examenes/{$this->examen->id_examen}/normas", [
                'tipo'        => 'GENERAL',
                'descripcion' => 'Intento de control',
            ]);

        $resCrear->assertStatus(403);

        // Intentar eliminar -> 403
        $regla = ReglaExamen::create([
            'id_examen'   => $this->examen->id_examen,
            'descripcion' => 'Norma para eliminar',
            'estado'      => 'ACTIVA',
        ]);

        $resEliminar = $this->withHeader('Authorization', "Bearer {$this->tokenControl}")
            ->deleteJson("/api/examenes/{$this->examen->id_examen}/normas/{$regla->id_regla_examen}");

        $resEliminar->assertStatus(403);
    }

    public function test_eliminar_norma_elimina_correctamente_y_actualiza_lista(): void
    {
        $regla = ReglaExamen::create([
            'id_examen'   => $this->examen->id_examen,
            'descripcion' => 'Norma para eliminar por admin',
            'estado'      => 'ACTIVA',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->tokenAdmin}")
            ->deleteJson("/api/examenes/{$this->examen->id_examen}/normas/{$regla->id_regla_examen}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Norma eliminada correctamente.']);

        $this->assertDatabaseMissing('regla_examen', [
            'id_regla_examen' => $regla->id_regla_examen,
        ]);
    }

    public function test_listar_normas_retorna_generales_y_particulares(): void
    {
        ReglaExamen::create([
            'id_examen'   => $this->examen->id_examen,
            'descripcion' => 'Norma general 1',
            'estado'      => 'ACTIVA',
        ]);

        ReglaIndividual::create([
            'id_habilitacion' => $this->habilitacion->id_habilitacion,
            'descripcion'     => 'Norma particular 1',
            'estado'          => 'ACTIVA',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->tokenControl}")
            ->getJson("/api/examenes/{$this->examen->id_examen}/normas");

        $response->assertStatus(200)
            ->assertJsonCount(2);
    }
}
