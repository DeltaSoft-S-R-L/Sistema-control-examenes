<?php

namespace Tests\Feature;

use App\Models\Ambiente;
use App\Models\AsignacionAmbiente;
use App\Models\Asignatura;
use App\Models\Carrera;
use App\Models\Estudiante;
use App\Models\Examen;
use App\Models\Facultad;
use App\Models\Habilitacion;
use App\Models\Ingreso;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\Usuario;
use App\Utilities\PasswordHasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamenEliminacionTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $rol = Rol::create(['nombre' => 'ADMINISTRADOR', 'descripcion' => 'Administrador']);
        $permiso = Permiso::create(['nombre' => 'GESTIONAR_EXAMENES', 'descripcion' => 'Gestionar exámenes']);
        $rol->permisos()->attach($permiso->id_permiso);
        $this->admin = Usuario::create([
            'nombre' => 'Admin', 'apellido' => 'Prueba', 'correo' => 'admin.borrado@test.bo',
            'username' => 'admin_borrado', 'password_hash' => PasswordHasher::hash('Clave123!'),
            'id_rol' => $rol->id_rol, 'estado' => 'ACTIVO',
        ]);

        $facultad = Facultad::create(['codigo' => 'FCT', 'nombre' => 'Facultad de prueba']);
        $carrera = Carrera::create(['codigo' => 'SIS', 'nombre' => 'Sistemas', 'id_facultad' => $facultad->id_facultad]);
        $asignatura = Asignatura::create(['codigo' => 'SIS-101', 'nombre' => 'Programación']);
        $ambiente = Ambiente::create(['codigo' => 'A-1', 'nombre' => 'Aula 1', 'capacidad' => 30, 'estado' => 'DISPONIBLE']);
        $estudiante = Estudiante::create([
            'ci' => '1234567', 'nombre' => 'Ana', 'apellido' => 'Prueba',
            'codigo_universitario' => '20240001', 'id_carrera' => $carrera->id_carrera,
            'correo' => 'ana.borrado@test.bo', 'estado' => 'ACTIVO',
        ]);

        $this->crearExamenConIngreso($asignatura, $ambiente, $estudiante, 'CANCELADO');
    }

    public function test_elimina_logicamente_examen_cancelado_y_conserva_ingreso_y_asignacion(): void
    {
        $examen = Examen::query()->where('estado', 'CANCELADO')->firstOrFail();
        $asignacion = $examen->asignacionesAmbiente()->firstOrFail();
        $ingresoId = Ingreso::query()->where('id_examen', $examen->id_examen)->value('id_ingreso');

        $this->actingAs($this->admin)->deleteJson("/api/examenes/{$examen->id_examen}")
            ->assertOk()
            ->assertJsonPath('message', 'Examen eliminado de la lista. Se conservaron los registros históricos asociados.');

        $this->assertSoftDeleted('examen', ['id_examen' => $examen->id_examen]);
        $this->assertDatabaseHas('asignacion_ambiente', ['id_asignacion_ambiente' => $asignacion->id_asignacion_ambiente]);
        $this->assertDatabaseHas('ingreso', ['id_ingreso' => $ingresoId]);
        $this->actingAs($this->admin)->getJson('/api/examenes')->assertOk()->assertJsonMissing(['id_examen' => $examen->id_examen]);
    }

    public function test_rechaza_eliminacion_si_examen_no_esta_programado_ni_cancelado(): void
    {
        $examen = Examen::create([
            'id_asignatura' => Asignatura::query()->value('id_asignatura'), 'nombre' => 'En curso',
            'fecha' => '2026-11-20', 'hora_inicio' => '10:00:00', 'duracion_minutos' => 60, 'estado' => 'EN_CURSO',
        ]);

        $this->actingAs($this->admin)->deleteJson("/api/examenes/{$examen->id_examen}")
            ->assertStatus(409);

        $this->assertDatabaseHas('examen', ['id_examen' => $examen->id_examen, 'deleted_at' => null]);
    }

    public function test_permite_baja_logica_de_examen_programado(): void
    {
        $examen = Examen::create([
            'id_asignatura' => Asignatura::query()->value('id_asignatura'), 'nombre' => 'Programado',
            'fecha' => '2026-11-20', 'hora_inicio' => '11:00:00', 'duracion_minutos' => 60, 'estado' => 'PROGRAMADO',
        ]);

        $this->actingAs($this->admin)->deleteJson("/api/examenes/{$examen->id_examen}")->assertOk();

        $this->assertSoftDeleted('examen', ['id_examen' => $examen->id_examen]);
    }

    private function crearExamenConIngreso(Asignatura $asignatura, Ambiente $ambiente, Estudiante $estudiante, string $estado): Examen
    {
        $examen = Examen::create([
            'id_asignatura' => $asignatura->id_asignatura, 'nombre' => 'Examen de prueba',
            'fecha' => '2026-11-20', 'hora_inicio' => '09:00:00', 'duracion_minutos' => 90, 'estado' => $estado,
        ]);
        $asignacion = AsignacionAmbiente::create(['id_examen' => $examen->id_examen, 'id_ambiente' => $ambiente->id_ambiente]);
        $habilitacion = Habilitacion::create([
            'id_estudiante' => $estudiante->id_estudiante, 'id_examen' => $examen->id_examen,
            'id_asignacion_ambiente' => $asignacion->id_asignacion_ambiente, 'estado' => 'HABILITADO',
        ]);
        Ingreso::create([
            'id_examen' => $examen->id_examen, 'id_habilitacion' => $habilitacion->id_habilitacion,
            'id_asignacion_ambiente' => $asignacion->id_asignacion_ambiente, 'id_usuario' => $this->admin->id_usuario,
        ]);

        return $examen;
    }
}
