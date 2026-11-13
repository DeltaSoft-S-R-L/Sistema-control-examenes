<?php

declare(strict_types=1);

namespace Tests\Feature\Sprint4;

use App\Models\Ambiente;
use App\Models\AsignacionAmbiente;
use App\Models\Asignatura;
use App\Models\Carrera;
use App\Models\Estudiante;
use App\Models\Examen;
use App\Models\Facultad;
use App\Models\Habilitacion;
use App\Models\Ingreso;
use App\Models\Rol;
use App\Models\Usuario;
use App\Utilities\PasswordHasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Ing0102IngresoResourceTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $controlUser;
    private string $controlToken;

    private Usuario $adminUser;
    private string $adminToken;

    private Usuario $docenteUser;
    private string $docenteToken;

    private Examen $examen;
    private Ambiente $ambiente;
    private AsignacionAmbiente $asignacionAmbiente;
    private Estudiante $estudiante;
    private Habilitacion $habilitacion;

    protected function setUp(): void
    {
        parent::setUp();

        $rolControl = Rol::create([
            'nombre' => 'CONTROL_INGRESO',
            'descripcion' => 'Personal de control de ingreso',
        ]);

        $rolAdmin = Rol::create([
            'nombre' => 'ADMINISTRADOR',
            'descripcion' => 'Administrador',
        ]);

        $rolDocente = Rol::create([
            'nombre' => 'DOCENTE',
            'descripcion' => 'Docente',
        ]);

        $this->controlUser = Usuario::create([
            'nombre' => 'Mateo',
            'apellido' => 'Rojas',
            'correo' => 'mateo.rojas@umss.edu.bo',
            'username' => 'mateo_control',
            'password_hash' => PasswordHasher::hash('Password123!'),
            'id_rol' => $rolControl->id_rol,
            'estado' => 'ACTIVO',
        ]);
        $this->controlToken = $this->controlUser->createToken('token-control')->plainTextToken;

        $this->adminUser = Usuario::create([
            'nombre' => 'Valeria',
            'apellido' => 'Soto',
            'correo' => 'valeria.soto@umss.edu.bo',
            'username' => 'valeria_admin',
            'password_hash' => PasswordHasher::hash('Password123!'),
            'id_rol' => $rolAdmin->id_rol,
            'estado' => 'ACTIVO',
        ]);
        $this->adminToken = $this->adminUser->createToken('token-admin')->plainTextToken;

        $this->docenteUser = Usuario::create([
            'nombre' => 'Javier',
            'apellido' => 'Docente',
            'correo' => 'javier.docente@umss.edu.bo',
            'username' => 'javier_docente',
            'password_hash' => PasswordHasher::hash('Password123!'),
            'id_rol' => $rolDocente->id_rol,
            'estado' => 'ACTIVO',
        ]);
        $this->docenteToken = $this->docenteUser->createToken('token-docente')->plainTextToken;

        $facultad = Facultad::create([
            'codigo' => 'FCYT',
            'nombre' => 'Facultad de Ciencias y Tecnologia',
        ]);

        $carrera = Carrera::create([
            'codigo' => 'SIS',
            'nombre' => 'Ingenieria de Sistemas',
            'id_facultad' => $facultad->id_facultad,
        ]);

        $asignatura = Asignatura::create([
            'codigo' => 'SIS-102',
            'nombre' => 'Estructura de Datos',
        ]);

        $this->examen = Examen::create([
            'id_asignatura' => $asignatura->id_asignatura,
            'nombre' => 'Examen Parcial Estructuras',
            'fecha' => '2026-11-25',
            'hora_inicio' => '10:00:00',
            'duracion_minutos' => 120,
            'estado' => 'PROGRAMADO',
        ]);

        $this->ambiente = Ambiente::create([
            'codigo' => '691B',
            'nombre' => 'Laboratorio 691B',
            'capacidad' => 40,
            'estado' => 'DISPONIBLE',
        ]);

        $this->asignacionAmbiente = AsignacionAmbiente::create([
            'id_examen' => $this->examen->id_examen,
            'id_ambiente' => $this->ambiente->id_ambiente,
        ]);

        $this->estudiante = Estudiante::create([
            'ci' => '6543210',
            'nombre' => 'Sofia',
            'apellido' => 'Mendoza',
            'codigo_universitario' => '20240055',
            'id_carrera' => $carrera->id_carrera,
            'correo' => 'sofia.mendoza@est.umss.edu',
            'estado' => 'ACTIVO',
        ]);

        $this->habilitacion = Habilitacion::create([
            'id_estudiante' => $this->estudiante->id_estudiante,
            'id_examen' => $this->examen->id_examen,
            'id_asignacion_ambiente' => $this->asignacionAmbiente->id_asignacion_ambiente,
            'estado' => 'HABILITADO',
        ]);
    }

    public function test_post_ingresos_retorna_201_con_recurso_ingreso_resource_y_estructura_valida(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->controlToken}",
            'Accept' => 'application/json',
        ])->postJson('/api/ingresos', [
            'id_examen' => $this->examen->id_examen,
            'id_habilitacion' => $this->habilitacion->id_habilitacion,
            'id_asignacion_ambiente' => $this->asignacionAmbiente->id_asignacion_ambiente,
        ]);

        $response->assertStatus(201);

        $response->assertJsonStructure([
            'id_ingreso',
            'id',
            'id_examen',
            'id_habilitacion',
            'id_asignacion_ambiente',
            'id_usuario',
            'controlador_id',
            'fecha_hora',
            'habilitacion',
            'usuario',
        ]);

        $response->assertJson([
            'id_examen' => $this->examen->id_examen,
            'id_habilitacion' => $this->habilitacion->id_habilitacion,
            'id_asignacion_ambiente' => $this->asignacionAmbiente->id_asignacion_ambiente,
            'id_usuario' => $this->controlUser->id_usuario,
            'controlador_id' => $this->controlUser->id_usuario,
        ]);

        $this->assertDatabaseHas('ingreso', [
            'id_examen' => $this->examen->id_examen,
            'id_habilitacion' => $this->habilitacion->id_habilitacion,
            'id_asignacion_ambiente' => $this->asignacionAmbiente->id_asignacion_ambiente,
            'id_usuario' => $this->controlUser->id_usuario,
        ]);
    }

    public function test_post_ingresos_con_administrador_persiste_su_id_usuario(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->adminToken}",
            'Accept' => 'application/json',
        ])->postJson('/api/ingresos', [
            'id_examen' => $this->examen->id_examen,
            'id_habilitacion' => $this->habilitacion->id_habilitacion,
            'id_asignacion_ambiente' => $this->asignacionAmbiente->id_asignacion_ambiente,
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'id_usuario' => $this->adminUser->id_usuario,
            'controlador_id' => $this->adminUser->id_usuario,
        ]);

        $this->assertDatabaseHas('ingreso', [
            'id_habilitacion' => $this->habilitacion->id_habilitacion,
            'id_usuario' => $this->adminUser->id_usuario,
        ]);
    }

    public function test_usuario_docente_es_rechazado_con_403_forbidden(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->docenteToken}",
            'Accept' => 'application/json',
        ])->postJson('/api/ingresos', [
            'id_examen' => $this->examen->id_examen,
            'id_habilitacion' => $this->habilitacion->id_habilitacion,
            'id_asignacion_ambiente' => $this->asignacionAmbiente->id_asignacion_ambiente,
        ]);

        $response->assertStatus(403);
        $this->assertSame(0, Ingreso::count());
    }

    public function test_peticion_sin_autenticacion_es_rechazada_con_401_unauthorized(): void
    {
        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->postJson('/api/ingresos', [
            'id_examen' => $this->examen->id_examen,
            'id_habilitacion' => $this->habilitacion->id_habilitacion,
            'id_asignacion_ambiente' => $this->asignacionAmbiente->id_asignacion_ambiente,
        ]);

        $response->assertStatus(401);
        $this->assertSame(0, Ingreso::count());
    }

    public function test_transaccion_persiste_registro_atomicamente(): void
    {
        $this->assertSame(0, Ingreso::count());

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->controlToken}",
            'Accept' => 'application/json',
        ])->postJson('/api/ingresos', [
            'id_examen' => $this->examen->id_examen,
            'id_habilitacion' => $this->habilitacion->id_habilitacion,
            'id_asignacion_ambiente' => $this->asignacionAmbiente->id_asignacion_ambiente,
        ]);

        $response->assertStatus(201);
        $this->assertSame(1, Ingreso::count());
    }
}
