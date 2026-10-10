<?php

namespace Tests\Feature;

use App\Models\Asignatura;
use App\Models\Carrera;
use App\Models\Estudiante;
use App\Models\Examen;
use App\Models\Facultad;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\Usuario;
use App\Utilities\PasswordHasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AsociarEstudianteExamenTest extends TestCase
{
    use RefreshDatabase;

    private Rol $rolDocente;
    private Usuario $docenteUser;
    private string $token;
    private Asignatura $asignatura;
    private Examen $examen1;
    private Examen $examen2;
    private Estudiante $estudiante1;
    private Estudiante $estudiante2;

    protected function setUp(): void
    {
        parent::setUp();

        // Permiso de habilitaciones requerido por PermissionMiddleware
        $permisoHabilitaciones = Permiso::create([
            'nombre'      => 'GESTIONAR_HABILITACIONES',
            'descripcion' => 'Permiso para gestionar habilitaciones',
        ]);

        // Rol y Usuario para autenticación
        $this->rolDocente = Rol::create([
            'nombre'      => 'DOCENTE',
            'descripcion' => 'Docente del sistema',
        ]);
        $this->rolDocente->permisos()->attach($permisoHabilitaciones->id_permiso);

        $this->docenteUser = Usuario::create([
            'nombre'        => 'Profesor',
            'apellido'      => 'Prueba',
            'correo'        => 'profesor@universidad.edu',
            'username'      => 'profesor_test',
            'password_hash' => PasswordHasher::hash('Password123'),
            'id_rol'        => $this->rolDocente->id_rol,
            'estado'        => 'ACTIVO',
        ]);
        $this->token = $this->docenteUser->createToken('test-token')->plainTextToken;

        // Estructura Académica (Facultad y Carrera requeridos para Estudiante)
        $facultad = Facultad::create([
            'codigo' => 'FCYT',
            'nombre' => 'Facultad de Ciencias y Tecnología',
        ]);

        $carrera = Carrera::create([
            'id_facultad' => $facultad->id_facultad,
            'codigo'      => 'SIS',
            'nombre'      => 'Ingeniería de Sistemas',
        ]);

        // Asignatura
        $this->asignatura = Asignatura::create([
            'codigo'      => 'SIS-101',
            'nombre'      => 'Programación I',
            'descripcion' => 'Introducción a la programación',
        ]);

        // Exámenes
        $this->examen1 = Examen::create([
            'id_asignatura'    => $this->asignatura->id_asignatura,
            'nombre'           => 'Primer Parcial Programación I',
            'fecha'            => '2026-10-20',
            'hora_inicio'      => '08:00',
            'duracion_minutos' => 90,
            'estado'           => 'programado',
        ]);

        $this->examen2 = Examen::create([
            'id_asignatura'    => $this->asignatura->id_asignatura,
            'nombre'           => 'Segundo Parcial Programación I',
            'fecha'            => '2026-11-25',
            'hora_inicio'      => '10:00',
            'duracion_minutos' => 90,
            'estado'           => 'programado',
        ]);

        // Estudiantes
        $this->estudiante1 = Estudiante::create([
            'ci'                   => '9876541',
            'nombre'               => 'Ana',
            'apellido'             => 'Gómez',
            'codigo_universitario' => '20220101',
            'id_carrera'           => $carrera->id_carrera,
            'correo'               => 'ana.gomez@universidad.edu',
            'estado'               => 'ACTIVO',
        ]);

        $this->estudiante2 = Estudiante::create([
            'ci'                   => '9876542',
            'nombre'               => 'Luis',
            'apellido'             => 'Paz',
            'codigo_universitario' => '20220102',
            'id_carrera'           => $carrera->id_carrera,
            'correo'               => 'luis.paz@universidad.edu',
            'estado'               => 'ACTIVO',
        ]);
    }

    private function authHeaders(): array
    {
        return [
            'Authorization' => "Bearer {$this->token}",
            'Accept'        => 'application/json',
        ];
    }

    /**
     * 1. Escenario 1: Asociación válida por primera vez.
     * Retorna HTTP 201 Created y persiste el registro en la BD.
     */
    public function test_asociacion_valida_por_primera_vez(): void
    {
        $payload = [
            'id_examen'     => $this->examen1->id_examen,
            'id_estudiante' => $this->estudiante1->id_estudiante,
            'estado'        => 'HABILITADO',
        ];

        $response = $this->postJson('/api/habilitaciones', $payload, $this->authHeaders());

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id_habilitacion',
                'id_estudiante',
                'id_examen',
                'estado',
                'estudiante',
                'examen',
            ])
            ->assertJson([
                'id_estudiante' => $this->estudiante1->id_estudiante,
                'id_examen'     => $this->examen1->id_examen,
                'estado'        => 'HABILITADO',
            ]);

        $this->assertDatabaseHas('habilitacion', [
            'id_estudiante' => $this->estudiante1->id_estudiante,
            'id_examen'     => $this->examen1->id_examen,
            'estado'        => 'HABILITADO',
        ]);

        $this->assertDatabaseCount('habilitacion', 1);
    }

    /**
     * 2. Escenario 2: Intento de duplicación / doble asignación.
     * Retorna HTTP 422 con mensaje: "El estudiante ya se encuentra asignado a este examen."
     */
    public function test_rechazo_por_asociacion_duplicada_retorna_422(): void
    {
        $payload = [
            'id_examen'     => $this->examen1->id_examen,
            'id_estudiante' => $this->estudiante1->id_estudiante,
            'estado'        => 'HABILITADO',
        ];

        // Primera asociación exitosa
        $this->postJson('/api/habilitaciones', $payload, $this->authHeaders())
            ->assertStatus(201);

        // Segundo intento de asociar al mismo estudiante al mismo examen
        $response = $this->postJson('/api/habilitaciones', $payload, $this->authHeaders());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['id_estudiante'])
            ->assertJsonFragment([
                'id_estudiante' => [
                    'El estudiante ya se encuentra asignado a este examen.',
                ],
            ]);

        // Asegurar que en base de datos solo existe 1 registro (persistencia única)
        $this->assertDatabaseCount('habilitacion', 1);
    }

    /**
     * 3. Un mismo estudiante SÍ puede ser asociado a exámenes distintos.
     */
    public function test_estudiante_puede_asociarse_a_dos_examenes_distintos(): void
    {
        $payload1 = [
            'id_examen'     => $this->examen1->id_examen,
            'id_estudiante' => $this->estudiante1->id_estudiante,
            'estado'        => 'HABILITADO',
        ];

        $payload2 = [
            'id_examen'     => $this->examen2->id_examen,
            'id_estudiante' => $this->estudiante1->id_estudiante,
            'estado'        => 'HABILITADO',
        ];

        $this->postJson('/api/habilitaciones', $payload1, $this->authHeaders())
            ->assertStatus(201);

        $this->postJson('/api/habilitaciones', $payload2, $this->authHeaders())
            ->assertStatus(201);

        $this->assertDatabaseCount('habilitacion', 2);
    }

    /**
     * 4. Dos estudiantes distintos SÍ pueden ser asociados al mismo examen.
     */
    public function test_dos_estudiantes_distintos_pueden_asociarse_al_mismo_examen(): void
    {
        $payload1 = [
            'id_examen'     => $this->examen1->id_examen,
            'id_estudiante' => $this->estudiante1->id_estudiante,
            'estado'        => 'HABILITADO',
        ];

        $payload2 = [
            'id_examen'     => $this->examen1->id_examen,
            'id_estudiante' => $this->estudiante2->id_estudiante,
            'estado'        => 'HABILITADO',
        ];

        $this->postJson('/api/habilitaciones', $payload1, $this->authHeaders())
            ->assertStatus(201);

        $this->postJson('/api/habilitaciones', $payload2, $this->authHeaders())
            ->assertStatus(201);

        $this->assertDatabaseCount('habilitacion', 2);
    }

    /**
     * 5. Escenario 3: Rechaza estudiante inexistente con 422.
     */
    public function test_rechaza_estudiante_inexistente(): void
    {
        $payload = [
            'id_examen'     => $this->examen1->id_examen,
            'id_estudiante' => 99999,
            'estado'        => 'HABILITADO',
        ];

        $response = $this->postJson('/api/habilitaciones', $payload, $this->authHeaders());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['id_estudiante'])
            ->assertJsonFragment([
                'id_estudiante' => [
                    'El estudiante seleccionado no existe.',
                ],
            ]);
    }

    /**
     * 6. Escenario 3: Rechaza examen inexistente con 422.
     */
    public function test_rechaza_examen_inexistente(): void
    {
        $payload = [
            'id_examen'     => 99999,
            'id_estudiante' => $this->estudiante1->id_estudiante,
            'estado'        => 'HABILITADO',
        ];

        $response = $this->postJson('/api/habilitaciones', $payload, $this->authHeaders());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['id_examen'])
            ->assertJsonFragment([
                'id_examen' => [
                    'El examen seleccionado no existe.',
                ],
            ]);
    }

    /**
     * 7. Petición no autenticada retorna 401 Unauthorized.
     */
    public function test_rechaza_peticion_sin_autenticacion(): void
    {
        $payload = [
            'id_examen'     => $this->examen1->id_examen,
            'id_estudiante' => $this->estudiante1->id_estudiante,
            'estado'        => 'HABILITADO',
        ];

        $response = $this->postJson('/api/habilitaciones', $payload, [
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(401);
    }

    /**
     * 8. BE-22: Rechaza estado inválido 'pendiente' con HTTP 422 (no revienta con 500 de BD).
     */
    public function test_rechaza_estado_invalido_pendiente_con_422(): void
    {
        $payload = [
            'id_examen'     => $this->examen1->id_examen,
            'id_estudiante' => $this->estudiante1->id_estudiante,
            'estado'        => 'pendiente',
        ];

        $response = $this->postJson('/api/habilitaciones', $payload, $this->authHeaders());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['estado'])
            ->assertJsonFragment([
                'estado' => ['El estado debe ser HABILITADO o NO_HABILITADO.'],
            ]);

        $this->assertDatabaseCount('habilitacion', 0);
    }

    /**
     * 9. BE-22: Rechaza estado inválido 'inhabilitado' con HTTP 422.
     */
    public function test_rechaza_estado_invalido_inhabilitado_con_422(): void
    {
        $payload = [
            'id_examen'     => $this->examen1->id_examen,
            'id_estudiante' => $this->estudiante1->id_estudiante,
            'estado'        => 'inhabilitado',
        ];

        $response = $this->postJson('/api/habilitaciones', $payload, $this->authHeaders());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['estado'])
            ->assertJsonFragment([
                'estado' => ['El estado debe ser HABILITADO o NO_HABILITADO.'],
            ]);

        $this->assertDatabaseCount('habilitacion', 0);
    }

    /**
     * 10. BE-22: Estado 'NO_HABILITADO' requiere motivo por restricción de base de datos.
     */
    public function test_estado_no_habilitado_requiere_motivo_y_persiste_correctamente(): void
    {
        // Sin motivo -> Rechazado con 422
        $responseSinMotivo = $this->postJson('/api/habilitaciones', [
            'id_examen'     => $this->examen1->id_examen,
            'id_estudiante' => $this->estudiante1->id_estudiante,
            'estado'        => 'NO_HABILITADO',
        ], $this->authHeaders());

        $responseSinMotivo->assertStatus(422)
            ->assertJsonValidationErrors(['motivo']);

        // Con motivo -> Creado con 201 y persistido en BD
        $responseConMotivo = $this->postJson('/api/habilitaciones', [
            'id_examen'     => $this->examen1->id_examen,
            'id_estudiante' => $this->estudiante1->id_estudiante,
            'estado'        => 'no_habilitado',
            'motivo'        => 'No presentó matrícula vigente',
        ], $this->authHeaders());

        $responseConMotivo->assertStatus(201)
            ->assertJsonPath('estado', 'NO_HABILITADO')
            ->assertJsonPath('motivo', 'No presentó matrícula vigente');

        $this->assertDatabaseHas('habilitacion', [
            'id_estudiante' => $this->estudiante1->id_estudiante,
            'id_examen'     => $this->examen1->id_examen,
            'estado'        => 'NO_HABILITADO',
            'motivo'        => 'No presentó matrícula vigente',
        ]);
    }

    /**
     * 11. BE-22: Actualización (PUT) rechaza estados 'pendiente' e 'inhabilitado' con 422.
     */
    public function test_actualizacion_rechaza_estados_invalidos_con_422(): void
    {
        $habilitacion = \App\Models\Habilitacion::create([
            'id_examen'     => $this->examen1->id_examen,
            'id_estudiante' => $this->estudiante1->id_estudiante,
            'estado'        => 'HABILITADO',
        ]);

        // Intentar actualizar a 'pendiente'
        $resPendiente = $this->putJson("/api/habilitaciones/{$habilitacion->id_habilitacion}", [
            'estado' => 'pendiente',
        ], $this->authHeaders());

        $resPendiente->assertStatus(422)
            ->assertJsonValidationErrors(['estado']);

        // Intentar actualizar a 'inhabilitado'
        $resInhabilitado = $this->putJson("/api/habilitaciones/{$habilitacion->id_habilitacion}", [
            'estado' => 'inhabilitado',
        ], $this->authHeaders());

        $resInhabilitado->assertStatus(422)
            ->assertJsonValidationErrors(['estado']);

        // Actualización válida a 'NO_HABILITADO' con motivo
        $resValida = $this->putJson("/api/habilitaciones/{$habilitacion->id_habilitacion}", [
            'estado' => 'no_habilitado',
            'motivo' => 'Falta de pago de matrícula',
        ], $this->authHeaders());

        $resValida->assertStatus(200)
            ->assertJsonPath('estado', 'NO_HABILITADO');
    }
}
