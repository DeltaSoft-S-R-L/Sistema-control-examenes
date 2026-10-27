<?php

namespace Tests\Feature;

use App\Models\Asignatura;
use App\Models\Carrera;
use App\Models\Estudiante;
use App\Models\Examen;
use App\Models\Facultad;
use App\Models\NormaExamen;
use App\Models\Rol;
use App\Models\Usuario;
use App\Utilities\PasswordHasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NormaExamenApiTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $adminUser;
    private Usuario $docenteUser;
    private Usuario $controlUser;
    private Usuario $estudianteUser;

    private string $adminToken;
    private string $docenteToken;
    private string $controlToken;
    private string $estudianteToken;

    private Examen $examen1;
    private Examen $examen2;
    private Estudiante $estudiante1;
    private Estudiante $estudiante2;

    protected function setUp(): void
    {
        parent::setUp();

        $rolAdmin = Rol::create([
            'nombre' => 'ADMINISTRADOR',
            'descripcion' => 'Administrador',
        ]);

        $rolDocente = Rol::create([
            'nombre' => 'DOCENTE',
            'descripcion' => 'Docente',
        ]);

        $rolControl = Rol::create([
            'nombre' => 'CONTROL_INGRESO',
            'descripcion' => 'Control de Ingreso',
        ]);

        $rolEstudiante = Rol::create([
            'nombre' => 'ESTUDIANTE',
            'descripcion' => 'Estudiante',
        ]);

        $this->adminUser = Usuario::create([
            'nombre' => 'Admin',
            'apellido' => 'General',
            'correo' => 'admin@test.com',
            'username' => 'admin_test',
            'password_hash' => PasswordHasher::hash('Password123'),
            'id_rol' => $rolAdmin->id_rol,
            'estado' => 'ACTIVO',
        ]);
        $this->adminToken = $this->adminUser->createToken('admin-token')->plainTextToken;

        $this->docenteUser = Usuario::create([
            'nombre' => 'Roberto',
            'apellido' => 'Gomez',
            'correo' => 'docente@test.com',
            'username' => 'docente_test',
            'password_hash' => PasswordHasher::hash('Password123'),
            'id_rol' => $rolDocente->id_rol,
            'estado' => 'ACTIVO',
        ]);
        $this->docenteToken = $this->docenteUser->createToken('docente-token')->plainTextToken;

        $this->controlUser = Usuario::create([
            'nombre' => 'Valeria',
            'apellido' => 'Rios',
            'correo' => 'control@test.com',
            'username' => 'control_test',
            'password_hash' => PasswordHasher::hash('Password123'),
            'id_rol' => $rolControl->id_rol,
            'estado' => 'ACTIVO',
        ]);
        $this->controlToken = $this->controlUser->createToken('control-token')->plainTextToken;

        $this->estudianteUser = Usuario::create([
            'nombre' => 'Carlos',
            'apellido' => 'Alumno',
            'correo' => 'alumno@test.com',
            'username' => 'alumno_test',
            'password_hash' => PasswordHasher::hash('Password123'),
            'id_rol' => $rolEstudiante->id_rol,
            'estado' => 'ACTIVO',
        ]);
        $this->estudianteToken = $this->estudianteUser->createToken('alumno-token')->plainTextToken;

        $facultad = Facultad::create([
            'codigo' => 'FCYT',
            'nombre' => 'Facultad de Ciencias y Tecnologia',
        ]);

        $carrera = Carrera::create([
            'id_facultad' => $facultad->id_facultad,
            'codigo' => 'SIS',
            'nombre' => 'Ingenieria de Sistemas',
        ]);

        $asignatura = Asignatura::create([
            'codigo' => 'SIS-101',
            'nombre' => 'Programacion I',
            'descripcion' => 'Introduccion a la programacion',
        ]);

        $this->examen1 = Examen::create([
            'id_asignatura' => $asignatura->id_asignatura,
            'nombre' => 'Primer Parcial Programacion I',
            'fecha' => '2026-10-20',
            'hora_inicio' => '08:00',
            'duracion_minutos' => 90,
            'estado' => 'PROGRAMADO',
        ]);

        $this->examen2 = Examen::create([
            'id_asignatura' => $asignatura->id_asignatura,
            'nombre' => 'Segundo Parcial Programacion I',
            'fecha' => '2026-11-25',
            'hora_inicio' => '10:00',
            'duracion_minutos' => 90,
            'estado' => 'PROGRAMADO',
        ]);

        $this->estudiante1 = Estudiante::create([
            'ci' => '1234567',
            'nombre' => 'Juan',
            'apellido' => 'Perez',
            'codigo_universitario' => '2022001',
            'id_carrera' => $carrera->id_carrera,
            'correo' => 'juan.perez@test.com',
            'estado' => 'ACTIVO',
        ]);

        $this->estudiante2 = Estudiante::create([
            'ci' => '7654321',
            'nombre' => 'Maria',
            'apellido' => 'Lopez',
            'codigo_universitario' => '2022002',
            'id_carrera' => $carrera->id_carrera,
            'correo' => 'maria.lopez@test.com',
            'estado' => 'ACTIVO',
        ]);
    }

    private function headers(string $token): array
    {
        return [
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json',
        ];
    }

    public function test_docente_puede_listar_normas_con_formato_resource_y_sin_campos_internos(): void
    {
        $normaGeneral = NormaExamen::create([
            'id_examen' => $this->examen1->id_examen,
            'id_estudiante' => null,
            'tipo' => 'GENERAL',
            'descripcion' => 'Uso obligatorio de boligrafo azul o negro.',
            'created_by' => $this->docenteUser->id_usuario,
        ]);

        $normaParticular = NormaExamen::create([
            'id_examen' => $this->examen1->id_examen,
            'id_estudiante' => $this->estudiante1->id_estudiante,
            'tipo' => 'PARTICULAR',
            'descripcion' => 'Permiso especial para rendir examen con calculadora cientifica.',
            'created_by' => $this->adminUser->id_usuario,
        ]);

        $response = $this->getJson(
            "/api/examenes/{$this->examen1->id_examen}/normas",
            $this->headers($this->docenteToken)
        );

        $response->assertStatus(200);

        $data = $response->json('data') ?? $response->json();
        $this->assertCount(2, $data);

        $primera = $data[0];
        $this->assertEquals($normaGeneral->id_norma_examen, $primera['id']);
        $this->assertEquals('GENERAL', $primera['tipo']);
        $this->assertEquals('Uso obligatorio de boligrafo azul o negro.', $primera['descripcion']);
        $this->assertEquals('Roberto', $primera['created_by']);
        $this->assertNotNull($primera['created_at']);
        $this->assertArrayNotHasKey('estudiante', $primera);
        $this->assertArrayNotHasKey('id_norma_examen', $primera);
        $this->assertArrayNotHasKey('id_examen', $primera);
        $this->assertArrayNotHasKey('id_estudiante', $primera);
        $this->assertArrayNotHasKey('updated_at', $primera);

        $segunda = $data[1];
        $this->assertEquals($normaParticular->id_norma_examen, $segunda['id']);
        $this->assertEquals('PARTICULAR', $segunda['tipo']);
        $this->assertEquals('Permiso especial para rendir examen con calculadora cientifica.', $segunda['descripcion']);
        $this->assertEquals('Admin', $segunda['created_by']);
        $this->assertNotNull($segunda['created_at']);
        $this->assertIsArray($segunda['estudiante']);
        $this->assertEquals($this->estudiante1->id_estudiante, $segunda['estudiante']['id']);
        $this->assertEquals('Juan', $segunda['estudiante']['nombres']);
        $this->assertEquals('Perez', $segunda['estudiante']['apellidos']);
        $this->assertEquals('1234567', $segunda['estudiante']['CI']);
        $this->assertArrayNotHasKey('id_norma_examen', $segunda);
        $this->assertArrayNotHasKey('id_examen', $segunda);
        $this->assertArrayNotHasKey('id_estudiante', $segunda);
        $this->assertArrayNotHasKey('updated_at', $segunda);
    }

    public function test_admin_y_control_pueden_listar_normas(): void
    {
        NormaExamen::create([
            'id_examen' => $this->examen1->id_examen,
            'id_estudiante' => null,
            'tipo' => 'GENERAL',
            'descripcion' => 'Norma de prueba',
            'created_by' => $this->docenteUser->id_usuario,
        ]);

        $responseAdmin = $this->getJson(
            "/api/examenes/{$this->examen1->id_examen}/normas",
            $this->headers($this->adminToken)
        );
        $responseAdmin->assertStatus(200);

        $responseControl = $this->getJson(
            "/api/examenes/{$this->examen1->id_examen}/normas",
            $this->headers($this->controlToken)
        );
        $responseControl->assertStatus(200);
    }

    public function test_usuario_sin_rol_autorizado_es_rechazado_con_403_al_listar(): void
    {
        $response = $this->getJson(
            "/api/examenes/{$this->examen1->id_examen}/normas",
            $this->headers($this->estudianteToken)
        );

        $response->assertStatus(403);
    }

    public function test_peticion_sin_token_es_rechazada_con_401(): void
    {
        $response = $this->getJson(
            "/api/examenes/{$this->examen1->id_examen}/normas",
            ['Accept' => 'application/json']
        );

        $response->assertStatus(401);
    }

    public function test_listar_normas_con_filtro_tipo(): void
    {
        NormaExamen::create([
            'id_examen' => $this->examen1->id_examen,
            'id_estudiante' => null,
            'tipo' => 'GENERAL',
            'descripcion' => 'Norma general 1',
            'created_by' => $this->docenteUser->id_usuario,
        ]);

        NormaExamen::create([
            'id_examen' => $this->examen1->id_examen,
            'id_estudiante' => $this->estudiante1->id_estudiante,
            'tipo' => 'PARTICULAR',
            'descripcion' => 'Norma particular 1',
            'created_by' => $this->docenteUser->id_usuario,
        ]);

        $responseGeneral = $this->getJson(
            "/api/examenes/{$this->examen1->id_examen}/normas?tipo=GENERAL",
            $this->headers($this->docenteToken)
        );
        $responseGeneral->assertStatus(200);
        $dataGeneral = $responseGeneral->json('data') ?? $responseGeneral->json();
        $this->assertCount(1, $dataGeneral);
        $this->assertEquals('GENERAL', $dataGeneral[0]['tipo']);

        $responseParticular = $this->getJson(
            "/api/examenes/{$this->examen1->id_examen}/normas?tipo=PARTICULAR",
            $this->headers($this->docenteToken)
        );
        $responseParticular->assertStatus(200);
        $dataParticular = $responseParticular->json('data') ?? $responseParticular->json();
        $this->assertCount(1, $dataParticular);
        $this->assertEquals('PARTICULAR', $dataParticular[0]['tipo']);
    }

    public function test_listar_normas_con_filtro_estudiante_id(): void
    {
        NormaExamen::create([
            'id_examen' => $this->examen1->id_examen,
            'id_estudiante' => $this->estudiante1->id_estudiante,
            'tipo' => 'PARTICULAR',
            'descripcion' => 'Norma estudiante 1',
            'created_by' => $this->docenteUser->id_usuario,
        ]);

        NormaExamen::create([
            'id_examen' => $this->examen1->id_examen,
            'id_estudiante' => $this->estudiante2->id_estudiante,
            'tipo' => 'PARTICULAR',
            'descripcion' => 'Norma estudiante 2',
            'created_by' => $this->docenteUser->id_usuario,
        ]);

        $response = $this->getJson(
            "/api/examenes/{$this->examen1->id_examen}/normas?estudiante_id={$this->estudiante1->id_estudiante}",
            $this->headers($this->docenteToken)
        );

        $response->assertStatus(200);
        $data = $response->json('data') ?? $response->json();
        $this->assertCount(1, $data);
        $this->assertEquals('Norma estudiante 1', $data[0]['descripcion']);
    }

    public function test_listar_normas_de_examen_inexistente_retorna_404(): void
    {
        $response = $this->getJson(
            '/api/examenes/99999/normas',
            $this->headers($this->docenteToken)
        );

        $response->assertStatus(404);
    }

    public function test_docente_puede_crear_norma_general(): void
    {
        $payload = [
            'tipo' => 'GENERAL',
            'descripcion' => 'Prohibido ingresar con telefonos celulares.',
        ];

        $response = $this->postJson(
            "/api/examenes/{$this->examen1->id_examen}/normas",
            $payload,
            $this->headers($this->docenteToken)
        );

        $response->assertStatus(201)
            ->assertJsonPath('tipo', 'GENERAL')
            ->assertJsonPath('descripcion', 'Prohibido ingresar con telefonos celulares.')
            ->assertJsonPath('created_by', 'Roberto');

        $this->assertDatabaseHas('norma_examen', [
            'id_examen' => $this->examen1->id_examen,
            'id_estudiante' => null,
            'tipo' => 'GENERAL',
            'descripcion' => 'Prohibido ingresar con telefonos celulares.',
            'created_by' => $this->docenteUser->id_usuario,
        ]);
    }

    public function test_admin_puede_crear_norma_particular(): void
    {
        $payload = [
            'tipo' => 'PARTICULAR',
            'descripcion' => 'Permiso para usar auriculares con cancelacion de ruido.',
            'estudiante_id' => $this->estudiante2->id_estudiante,
        ];

        $response = $this->postJson(
            "/api/examenes/{$this->examen1->id_examen}/normas",
            $payload,
            $this->headers($this->adminToken)
        );

        $response->assertStatus(201)
            ->assertJsonPath('tipo', 'PARTICULAR')
            ->assertJsonPath('estudiante.id', $this->estudiante2->id_estudiante)
            ->assertJsonPath('estudiante.nombres', 'Maria')
            ->assertJsonPath('estudiante.apellidos', 'Lopez')
            ->assertJsonPath('estudiante.CI', '7654321')
            ->assertJsonPath('created_by', 'Admin');

        $this->assertDatabaseHas('norma_examen', [
            'id_examen' => $this->examen1->id_examen,
            'id_estudiante' => $this->estudiante2->id_estudiante,
            'tipo' => 'PARTICULAR',
            'created_by' => $this->adminUser->id_usuario,
        ]);
    }

    public function test_rol_control_no_puede_crear_normas_retorna_403(): void
    {
        $payload = [
            'tipo' => 'GENERAL',
            'descripcion' => 'Norma creada por control',
        ];

        $response = $this->postJson(
            "/api/examenes/{$this->examen1->id_examen}/normas",
            $payload,
            $this->headers($this->controlToken)
        );

        $response->assertStatus(403);
    }

    public function test_crear_norma_en_examen_inexistente_retorna_404(): void
    {
        $payload = [
            'tipo' => 'GENERAL',
            'descripcion' => 'Norma en examen que no existe',
        ];

        $response = $this->postJson(
            '/api/examenes/99999/normas',
            $payload,
            $this->headers($this->docenteToken)
        );

        $response->assertStatus(404);
    }

    public function test_crear_norma_valida_campos_y_retorna_422(): void
    {
        $responseSinDescripcion = $this->postJson(
            "/api/examenes/{$this->examen1->id_examen}/normas",
            ['tipo' => 'GENERAL'],
            $this->headers($this->docenteToken)
        );
        $responseSinDescripcion->assertStatus(422)
            ->assertJsonValidationErrors(['descripcion']);

        $responseTipoInvalido = $this->postJson(
            "/api/examenes/{$this->examen1->id_examen}/normas",
            [
                'tipo' => 'INVALIDO',
                'descripcion' => 'Descripcion valida',
            ],
            $this->headers($this->docenteToken)
        );
        $responseTipoInvalido->assertStatus(422)
            ->assertJsonValidationErrors(['tipo']);

        $responseParticularSinEstudiante = $this->postJson(
            "/api/examenes/{$this->examen1->id_examen}/normas",
            [
                'tipo' => 'PARTICULAR',
                'descripcion' => 'Descripcion valida',
            ],
            $this->headers($this->docenteToken)
        );
        $responseParticularSinEstudiante->assertStatus(422)
            ->assertJsonValidationErrors(['estudiante_id']);

        $responseGeneralConEstudiante = $this->postJson(
            "/api/examenes/{$this->examen1->id_examen}/normas",
            [
                'tipo' => 'GENERAL',
                'descripcion' => 'Descripcion valida',
                'estudiante_id' => $this->estudiante1->id_estudiante,
            ],
            $this->headers($this->docenteToken)
        );
        $responseGeneralConEstudiante->assertStatus(422)
            ->assertJsonValidationErrors(['estudiante_id']);

        $responseEstudianteInexistente = $this->postJson(
            "/api/examenes/{$this->examen1->id_examen}/normas",
            [
                'tipo' => 'PARTICULAR',
                'descripcion' => 'Descripcion valida',
                'estudiante_id' => 99999,
            ],
            $this->headers($this->docenteToken)
        );
        $responseEstudianteInexistente->assertStatus(422)
            ->assertJsonValidationErrors(['estudiante_id']);
    }

    public function test_docente_puede_eliminar_norma_de_un_examen_retorna_204(): void
    {
        $norma = NormaExamen::create([
            'id_examen' => $this->examen1->id_examen,
            'id_estudiante' => null,
            'tipo' => 'GENERAL',
            'descripcion' => 'Norma para eliminar',
            'created_by' => $this->docenteUser->id_usuario,
        ]);

        $response = $this->deleteJson(
            "/api/examenes/{$this->examen1->id_examen}/normas/{$norma->id_norma_examen}",
            [],
            $this->headers($this->docenteToken)
        );

        $response->assertStatus(204);

        $this->assertDatabaseMissing('norma_examen', [
            'id_norma_examen' => $norma->id_norma_examen,
        ]);
    }

    public function test_eliminar_norma_de_otro_examen_retorna_404_y_no_se_elimina(): void
    {
        $normaDeExamen2 = NormaExamen::create([
            'id_examen' => $this->examen2->id_examen,
            'id_estudiante' => null,
            'tipo' => 'GENERAL',
            'descripcion' => 'Norma perteneciente a examen 2',
            'created_by' => $this->docenteUser->id_usuario,
        ]);

        $response = $this->deleteJson(
            "/api/examenes/{$this->examen1->id_examen}/normas/{$normaDeExamen2->id_norma_examen}",
            [],
            $this->headers($this->docenteToken)
        );

        $response->assertStatus(404);

        $this->assertDatabaseHas('norma_examen', [
            'id_norma_examen' => $normaDeExamen2->id_norma_examen,
            'id_examen' => $this->examen2->id_examen,
        ]);
    }

    public function test_eliminar_norma_inexistente_o_examen_inexistente_retorna_404(): void
    {
        $responseExamenInexistente = $this->deleteJson(
            "/api/examenes/99999/normas/1",
            [],
            $this->headers($this->docenteToken)
        );
        $responseExamenInexistente->assertStatus(404);

        $responseNormaInexistente = $this->deleteJson(
            "/api/examenes/{$this->examen1->id_examen}/normas/99999",
            [],
            $this->headers($this->docenteToken)
        );
        $responseNormaInexistente->assertStatus(404);
    }

    public function test_rol_control_no_puede_eliminar_normas_retorna_403(): void
    {
        $norma = NormaExamen::create([
            'id_examen' => $this->examen1->id_examen,
            'id_estudiante' => null,
            'tipo' => 'GENERAL',
            'descripcion' => 'Norma protegida',
            'created_by' => $this->docenteUser->id_usuario,
        ]);

        $response = $this->deleteJson(
            "/api/examenes/{$this->examen1->id_examen}/normas/{$norma->id_norma_examen}",
            [],
            $this->headers($this->controlToken)
        );

        $response->assertStatus(403);

        $this->assertDatabaseHas('norma_examen', [
            'id_norma_examen' => $norma->id_norma_examen,
        ]);
    }
}
