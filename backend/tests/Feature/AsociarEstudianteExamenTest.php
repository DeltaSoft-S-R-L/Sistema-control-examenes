<?php

namespace Tests\Feature;

use App\Models\Asignatura;
use App\Models\Estudiante;
use App\Models\Examen;
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

        // Rol y Usuario para autenticación
        $this->rolDocente = Rol::create([
            'nombre'      => 'DOCENTE',
            'descripcion' => 'Docente del sistema',
        ]);

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
            'correo'               => 'ana.gomez@universidad.edu',
            'estado'               => 'ACTIVO',
        ]);

        $this->estudiante2 = Estudiante::create([
            'ci'                   => '9876542',
            'nombre'               => 'Luis',
            'apellido'             => 'Paz',
            'codigo_universitario' => '20220102',
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
}
