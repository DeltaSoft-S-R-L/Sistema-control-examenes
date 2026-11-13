<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Ambiente;
use App\Models\AsignacionAmbiente;
use App\Models\Asignatura;
use App\Models\Carrera;
use App\Models\Estudiante;
use App\Models\Examen;
use App\Models\Facultad;
use App\Models\Habilitacion;
use App\Models\IngresoExamen;
use App\Models\Rol;
use App\Models\Usuario;
use App\Utilities\PasswordHasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IngresoExamenModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_modelo_ingreso_examen_tiene_relaciones_correctas(): void
    {
        $rol = Rol::create([
            'nombre' => 'CONTROL_INGRESO',
            'descripcion' => 'Control',
        ]);

        $usuario = Usuario::create([
            'nombre' => 'Carlos',
            'apellido' => 'Operador',
            'correo' => 'carlos.operador@umss.edu.bo',
            'username' => 'carlos_operador',
            'password_hash' => PasswordHasher::hash('Pass123!'),
            'id_rol' => $rol->id_rol,
            'estado' => 'ACTIVO',
        ]);

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
            'codigo' => 'SIS-101',
            'nombre' => 'Programacion I',
        ]);

        $examen = Examen::create([
            'id_asignatura' => $asignatura->id_asignatura,
            'nombre' => 'Primer Parcial Programacion',
            'fecha' => '2026-11-20',
            'hora_inicio' => '08:00:00',
            'duracion_minutos' => 90,
            'estado' => 'PROGRAMADO',
        ]);

        $ambiente = Ambiente::create([
            'codigo' => 'A-101',
            'nombre' => 'Aula 101',
            'capacidad' => 50,
            'estado' => 'DISPONIBLE',
        ]);

        $asignacion = AsignacionAmbiente::create([
            'id_examen' => $examen->id_examen,
            'id_ambiente' => $ambiente->id_ambiente,
        ]);

        $estudiante = Estudiante::create([
            'ci' => '12345678',
            'nombre' => 'Juan',
            'apellido' => 'Perez',
            'codigo_universitario' => '20240001',
            'id_carrera' => $carrera->id_carrera,
            'correo' => 'juan.perez@est.umss.edu',
            'estado' => 'ACTIVO',
        ]);

        $habilitacion = Habilitacion::create([
            'id_estudiante' => $estudiante->id_estudiante,
            'id_examen' => $examen->id_examen,
            'estado' => 'HABILITADO',
        ]);

        $ingresoExamen = IngresoExamen::create([
            'id_examen' => $examen->id_examen,
            'id_habilitacion' => $habilitacion->id_habilitacion,
            'id_asignacion_ambiente' => $asignacion->id_asignacion_ambiente,
            'id_usuario' => $usuario->id_usuario,
            'fecha_hora' => now(),
        ]);

        $this->assertInstanceOf(IngresoExamen::class, $ingresoExamen);
        $this->assertEquals($examen->id_examen, $ingresoExamen->examen->id_examen);
        $this->assertEquals($usuario->id_usuario, $ingresoExamen->usuario->id_usuario);
        $this->assertEquals($estudiante->id_estudiante, $ingresoExamen->estudiante->id_estudiante);
        $this->assertEquals($ambiente->id_ambiente, $ingresoExamen->ambiente->id_ambiente);
        $this->assertEquals($habilitacion->id_habilitacion, $ingresoExamen->habilitacion->id_habilitacion);
        $this->assertEquals($asignacion->id_asignacion_ambiente, $ingresoExamen->asignacionAmbiente->id_asignacion_ambiente);
    }
}
