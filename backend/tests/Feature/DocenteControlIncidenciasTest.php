<?php

namespace Tests\Feature;

use App\Models\Asignatura;
use App\Models\Carrera;
use App\Models\Estudiante;
use App\Models\Examen;
use App\Models\Facultad;
use App\Models\Habilitacion;
use App\Models\Rol;
use App\Models\Usuario;
use App\Utilities\PasswordHasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DocenteControlIncidenciasTest extends TestCase
{
    use RefreshDatabase;

    public function test_docente_registra_y_consulta_incidencias_solo_de_su_carrera(): void
    {
        $sufijo = strtolower(bin2hex(random_bytes(4)));
        $rol = Rol::create(['nombre' => 'DOCENTE', 'descripcion' => 'Docente']);
        $docente = Usuario::create([
            'nombre' => 'Docente',
            'apellido' => 'Prueba',
            'correo' => "docente_{$sufijo}@example.com",
            'username' => "docente_{$sufijo}",
            'password_hash' => PasswordHasher::hash('Password123'),
            'id_rol' => $rol->id_rol,
            'estado' => 'ACTIVO',
        ]);
        $token = $docente->createToken('docente-incidencias')->plainTextToken;

        $facultad = Facultad::create(['codigo' => "F{$sufijo}", 'nombre' => 'Facultad de prueba']);
        $carreraAsignada = Carrera::create([
            'codigo' => "CA{$sufijo}",
            'nombre' => 'Carrera asignada',
            'id_facultad' => $facultad->id_facultad,
        ]);
        $carreraNoAsignada = Carrera::create([
            'codigo' => "CN{$sufijo}",
            'nombre' => 'Carrera no asignada',
            'id_facultad' => $facultad->id_facultad,
        ]);
        $asignatura = Asignatura::create([
            'codigo' => "A{$sufijo}",
            'nombre' => 'Asignatura de prueba',
            'descripcion' => 'Materia temporal',
        ]);
        DB::table('carrera_asignatura')->insert([
            'id_carrera' => $carreraAsignada->id_carrera,
            'id_asignatura' => $asignatura->id_asignatura,
        ]);
        DB::table('docente_asignatura')->insert([
            'id_usuario' => $docente->id_usuario,
            'id_carrera' => $carreraAsignada->id_carrera,
            'id_asignatura' => $asignatura->id_asignatura,
        ]);
        $examen = Examen::create([
            'id_asignatura' => $asignatura->id_asignatura,
            'nombre' => 'Examen de prueba',
            'fecha' => '2026-11-20',
            'hora_inicio' => '08:00',
            'duracion_minutos' => 90,
            'estado' => 'PROGRAMADO',
        ]);
        $estudianteAsignado = Estudiante::create([
            'ci' => "CI{$sufijo}1",
            'nombre' => 'Ana',
            'apellido' => 'Asignada',
            'codigo_universitario' => "SIS{$sufijo}1",
            'id_carrera' => $carreraAsignada->id_carrera,
            'correo' => "ana_{$sufijo}@example.com",
            'estado' => 'ACTIVO',
        ]);
        $estudianteNoAsignado = Estudiante::create([
            'ci' => "CI{$sufijo}2",
            'nombre' => 'Luis',
            'apellido' => 'No asignado',
            'codigo_universitario' => "SIS{$sufijo}2",
            'id_carrera' => $carreraNoAsignada->id_carrera,
            'correo' => "luis_{$sufijo}@example.com",
            'estado' => 'ACTIVO',
        ]);
        Habilitacion::create([
            'id_examen' => $examen->id_examen,
            'id_estudiante' => $estudianteAsignado->id_estudiante,
            'estado' => 'HABILITADO',
        ]);
        Habilitacion::create([
            'id_examen' => $examen->id_examen,
            'id_estudiante' => $estudianteNoAsignado->id_estudiante,
            'estado' => 'HABILITADO',
        ]);

        $headers = ['Authorization' => "Bearer {$token}"];
        $this->postJson('/api/incidencias', [
            'id_examen' => $examen->id_examen,
            'id_estudiante' => $estudianteAsignado->id_estudiante,
            'tipo' => 'OTRO',
            'descripcion' => 'Incidencia de prueba',
        ], $headers)->assertCreated();

        $this->postJson('/api/incidencias', [
            'id_examen' => $examen->id_examen,
            'id_estudiante' => $estudianteNoAsignado->id_estudiante,
            'tipo' => 'OTRO',
            'descripcion' => 'Incidencia fuera de alcance',
        ], $headers)->assertForbidden();

        $this->getJson('/api/incidencias', $headers)
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.id_estudiante', $estudianteAsignado->id_estudiante);

        $this->getJson('/api/reportes/examenes', $headers)
            ->assertOk()
            ->assertJsonCount(1);
        $this->getJson('/api/reportes/examenes/' . $examen->id_examen . '/asistencia', $headers)
            ->assertOk()
            ->assertJsonPath('resumen.habilitados', 1)
            ->assertJsonPath('data.0.id_estudiante', $estudianteAsignado->id_estudiante);

        // Registro de incidencia usando CI en vez de id_estudiante
        $this->postJson('/api/incidencias', [
            'id_examen' => $examen->id_examen,
            'id_estudiante' => $estudianteAsignado->ci,
            'tipo' => 'IDENTIFICACION',
            'descripcion' => 'Incidencia registrada mediante CI',
        ], $headers)
            ->assertCreated()
            ->assertJsonPath('id_estudiante', $estudianteAsignado->id_estudiante);

        // Registro de incidencia usando código SIS
        $this->postJson('/api/incidencias', [
            'id_examen' => $examen->id_examen,
            'id_estudiante' => $estudianteAsignado->codigo_universitario,
            'tipo' => 'OTRO',
            'descripcion' => 'Incidencia registrada mediante SIS',
        ], $headers)
            ->assertCreated()
            ->assertJsonPath('id_estudiante', $estudianteAsignado->id_estudiante);
    }
}
