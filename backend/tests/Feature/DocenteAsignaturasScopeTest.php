<?php

namespace Tests\Feature;

use App\Models\Asignatura;
use App\Models\Carrera;
use App\Models\Examen;
use App\Models\Facultad;
use App\Models\Rol;
use App\Models\Usuario;
use App\Utilities\PasswordHasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DocenteAsignaturasScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_docente_solo_ve_sus_asignaturas_en_el_catalogo_y_admin_ve_todas(): void
    {
        $rolAdmin = Rol::create(['nombre' => 'ADMINISTRADOR', 'descripcion' => 'Administrador']);
        $rolDocente = Rol::create(['nombre' => 'DOCENTE', 'descripcion' => 'Docente']);

        $admin = Usuario::create([
            'nombre' => 'Admin',
            'apellido' => 'Sistema',
            'correo' => 'admin.asig@test.bo',
            'username' => 'admin_asig',
            'password_hash' => PasswordHasher::hash('Clave123!'),
            'id_rol' => $rolAdmin->id_rol,
            'estado' => 'ACTIVO',
        ]);

        $docente = Usuario::create([
            'nombre' => 'Docente',
            'apellido' => 'Prueba',
            'correo' => 'docente.asig@test.bo',
            'username' => 'docente_asig',
            'password_hash' => PasswordHasher::hash('Clave123!'),
            'id_rol' => $rolDocente->id_rol,
            'estado' => 'ACTIVO',
        ]);

        $facultad = Facultad::create(['codigo' => 'FCYT', 'nombre' => 'Tecnologia']);
        $carrera = Carrera::create([
            'codigo' => 'INF',
            'nombre' => 'Informatica',
            'id_facultad' => $facultad->id_facultad,
        ]);

        $propia = Asignatura::create(['codigo' => 'INF-101', 'nombre' => 'Algebra I']);
        $ajena = Asignatura::create(['codigo' => 'INF-202', 'nombre' => 'Base de Datos II']);

        DB::table('carrera_asignatura')->insert([
            ['id_carrera' => $carrera->id_carrera, 'id_asignatura' => $propia->id_asignatura],
            ['id_carrera' => $carrera->id_carrera, 'id_asignatura' => $ajena->id_asignatura],
        ]);

        DB::table('docente_asignatura')->insert([
            'id_usuario' => $docente->id_usuario,
            'id_carrera' => $carrera->id_carrera,
            'id_asignatura' => $propia->id_asignatura,
        ]);

        $this->actingAs($docente)
            ->getJson('/api/asignaturas')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id_asignatura', $propia->id_asignatura);

        $this->actingAs($admin)
            ->getJson('/api/asignaturas')
            ->assertOk()
            ->assertJsonCount(2);
    }

    public function test_examenes_soporta_per_page_personalizado_hasta_100(): void
    {
        $rolAdmin = Rol::create(['nombre' => 'ADMINISTRADOR', 'descripcion' => 'Administrador']);
        $admin = Usuario::create([
            'nombre' => 'Admin',
            'apellido' => 'Pag',
            'correo' => 'admin.pag@test.bo',
            'username' => 'admin_pag',
            'password_hash' => PasswordHasher::hash('Clave123!'),
            'id_rol' => $rolAdmin->id_rol,
            'estado' => 'ACTIVO',
        ]);

        $asignatura = Asignatura::create(['codigo' => 'MAT-100', 'nombre' => 'Calculo']);

        for ($i = 1; $i <= 25; $i++) {
            Examen::create([
                'id_asignatura' => $asignatura->id_asignatura,
                'nombre' => "Examen {$i}",
                'fecha' => '2026-12-01',
                'hora_inicio' => '08:00:00',
                'duracion_minutos' => 90,
                'estado' => 'PROGRAMADO',
            ]);
        }

        $this->actingAs($admin)
            ->getJson('/api/examenes?per_page=100')
            ->assertOk()
            ->assertJsonPath('per_page', 100)
            ->assertJsonCount(25, 'data');

        $this->actingAs($admin)
            ->getJson('/api/examenes?per_page=500')
            ->assertOk()
            ->assertJsonPath('per_page', 100);
    }
}
