<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;
use App\Models\Asignatura;
use App\Models\Carrera;
use App\Models\Estudiante;
use App\Models\Examen;
use App\Models\Facultad;
use App\Models\Habilitacion;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\Usuario;
use App\Utilities\PasswordHasher;
use Illuminate\Support\Str;

class MotivoInhabilitacionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $connection = DB::connection();
        $database = $connection->selectOne(
            'SELECT current_database() AS nombre'
        )->nombre;

        if (
            $connection->getDriverName() !== 'pgsql' ||
            $database !== 'control_examenes_pruebas'
        ) {
            throw new RuntimeException(
                'Pruebas detenidas: la conexion no es la base autorizada.'
            );
        }

        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        try {
            if (DB::connection()->transactionLevel() > 0) {
                DB::rollBack();
            }
        } finally {
            parent::tearDown();
        }
    }

    private function crearDatosBase(): array
    {
        $sufijo = strtolower(Str::random(8));

        $permiso = Permiso::firstOrCreate(
            ['nombre' => 'GESTIONAR_HABILITACIONES'],
            ['descripcion' => 'Gestionar habilitaciones de estudiantes']
        );

        $rol = Rol::firstOrCreate([
            'nombre' => 'control'
        ], [
            'descripcion' => 'Personal de control de pruebas'
        ]);

        if (!$rol->permisos()->where('permiso.id_permiso', $permiso->id_permiso)->exists()) {
            $rol->permisos()->attach($permiso->id_permiso);
        }

        $usuario = Usuario::create([
            'nombre' => 'Control',
            'apellido' => 'Test',
            'correo' => "control_{$sufijo}@example.com",
            'username' => "control_{$sufijo}",
            'password_hash' => PasswordHasher::hash('Password123'),
            'id_rol' => $rol->id_rol,
            'estado' => 'ACTIVO',
        ]);

        $token = $usuario->createToken('test-token')->plainTextToken;

        $facultad = Facultad::create([
            'codigo' => 'F' . $sufijo,
            'nombre' => 'Facultad Test'
        ]);

        $carrera = Carrera::create([
            'id_facultad' => $facultad->id_facultad,
            'codigo' => 'C' . $sufijo,
            'nombre' => 'Carrera Test'
        ]);

        $asignatura = Asignatura::create([
            'codigo' => 'A' . $sufijo,
            'nombre' => 'Asignatura Test',
        ]);

        $examen = Examen::create([
            'id_asignatura' => $asignatura->id_asignatura,
            'nombre' => 'Examen Test',
            'fecha' => '2026-12-01',
            'hora_inicio' => '10:00',
            'duracion_minutos' => 90,
            'estado' => 'PROGRAMADO',
        ]);

        $estudiante = Estudiante::create([
            'ci' => $sufijo . '123',
            'nombre' => 'Estudiante',
            'apellido' => 'Test',
            'codigo_universitario' => $sufijo . '123',
            'id_carrera' => $carrera->id_carrera,
            'correo' => "est_{$sufijo}@example.com",
            'estado' => 'ACTIVO',
        ]);

        return [$examen, $estudiante, $token, $usuario];
    }

    public function test_retorna_401_sin_token(): void
    {
        [$examen, $estudiante] = $this->crearDatosBase();

        $response = $this->getJson("/api/examenes/{$examen->id_examen}/estudiantes/{$estudiante->id_estudiante}/inhabilitacion");

        $response->assertStatus(401);
    }

    public function test_retorna_404_si_estudiante_no_esta_asociado_al_examen(): void
    {
        [$examen, $estudiante, $token] = $this->crearDatosBase();

        $response = $this->getJson(
            "/api/examenes/{$examen->id_examen}/estudiantes/{$estudiante->id_estudiante}/inhabilitacion",
            ['Authorization' => "Bearer {$token}"]
        );

        $response->assertStatus(404);
    }

    public function test_retorna_200_y_motivo_null_si_estudiante_esta_habilitado(): void
    {
        [$examen, $estudiante, $token] = $this->crearDatosBase();

        Habilitacion::create([
            'id_examen' => $examen->id_examen,
            'id_estudiante' => $estudiante->id_estudiante,
            'estado' => 'HABILITADO'
        ]);

        $response = $this->getJson(
            "/api/examenes/{$examen->id_examen}/estudiantes/{$estudiante->id_estudiante}/inhabilitacion",
            ['Authorization' => "Bearer {$token}"]
        );

        $response->assertStatus(200)
                 ->assertJson([
                     'habilitado' => true,
                     'motivo' => null
                 ]);
    }

    public function test_retorna_200_con_datos_si_estudiante_esta_inhabilitado(): void
    {
        [$examen, $estudiante, $token, $usuario] = $this->crearDatosBase();

        Habilitacion::create([
            'id_examen' => $examen->id_examen,
            'id_estudiante' => $estudiante->id_estudiante,
            'estado' => 'NO_HABILITADO',
            'motivo_tipo' => 'MORA', 
            'motivo_detalle' => 'Deuda pendiente',
            'inhabilitado_por' => $usuario->id_usuario
        ]);

        $response = $this->getJson(
            "/api/examenes/{$examen->id_examen}/estudiantes/{$estudiante->id_estudiante}/inhabilitacion",
            ['Authorization' => "Bearer {$token}"]
        );

        $response->assertStatus(200)
                 ->assertJsonPath('habilitado', false)
                 ->assertJsonPath('motivo.tipo', 'MORA')
                 ->assertJsonStructure([
                     'habilitado',
                     'motivo' => [
                         'tipo', 'etiqueta', 'detalle'
                     ]
                 ]);
    }

    public function test_retorna_200_con_cada_tipo_de_motivo(): void
    {
        [$examen, $estudiante, $token, $usuario] = $this->crearDatosBase();
        $tipos = ['ACADEMICO', 'MORA', 'DOCUMENTACION', 'OTRO'];

        foreach ($tipos as $tipo) {
            Habilitacion::updateOrCreate(
                ['id_examen' => $examen->id_examen, 'id_estudiante' => $estudiante->id_estudiante],
                ['estado' => 'NO_HABILITADO', 'motivo_tipo' => $tipo, 'inhabilitado_por' => $usuario->id_usuario]
            );

            $this->getJson("/api/examenes/{$examen->id_examen}/estudiantes/{$estudiante->id_estudiante}/inhabilitacion", ['Authorization' => "Bearer {$token}"])
                 ->assertStatus(200)
                 ->assertJsonPath('motivo.tipo', $tipo);
        }
    }

    public function test_habilitar_estudiante_limpia_el_motivo(): void
    {
        [$examen, $estudiante, $token, $usuario] = $this->crearDatosBase();

        $habilitacion = Habilitacion::create([
            'id_examen' => $examen->id_examen, 'id_estudiante' => $estudiante->id_estudiante,
            'estado' => 'NO_HABILITADO', 'motivo_tipo' => 'MORA', 'inhabilitado_por' => $usuario->id_usuario
        ]);

        $this->putJson("/api/habilitaciones/{$habilitacion->id_habilitacion}", ['estado' => 'HABILITADO'], ['Authorization' => "Bearer {$token}"])
             ->assertStatus(200);

        $this->assertDatabaseHas('habilitacion', [
            'id_habilitacion' => $habilitacion->id_habilitacion,
            'motivo_tipo' => null
        ]);
    }

    public function test_inhabilitar_sin_motivo_guarda_otro_por_defecto(): void
    {
        [$examen, $estudiante, $token] = $this->crearDatosBase();

        $this->postJson('/api/habilitaciones', [
            'id_examen' => $examen->id_examen,
            'id_estudiante' => $estudiante->id_estudiante,
            'estado' => 'NO_HABILITADO'
        ], ['Authorization' => "Bearer {$token}"]);

        $this->assertDatabaseHas('habilitacion', [
            'id_examen' => $examen->id_examen,
            'id_estudiante' => $estudiante->id_estudiante,
            'motivo_tipo' => 'OTRO'
        ]);
    }
}