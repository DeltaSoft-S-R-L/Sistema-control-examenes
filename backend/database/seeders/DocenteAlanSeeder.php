<?php

namespace Database\Seeders;

use App\Models\AsignacionAmbiente;
use App\Models\Estudiante;
use App\Models\Examen;
use App\Models\Habilitacion;
use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DocenteAlanSeeder extends Seeder
{
    public function run(): void
    {
        $idCarrera = 21;    // Ingeniería Informática
        $idAsignatura = 75; // ALGORITMOS AVANZADOS

        $docente = Usuario::where('username', 'Alan')->first();
        if (!$docente) {
            $docente = Usuario::create([
                'username'      => 'Alan',
                'nombre'        => 'Alan',
                'apellido'      => 'Peralta',
                'correo'        => '202400765@umss.edu.bo',
                'password_hash' => \App\Utilities\PasswordHasher::hash('alan123'),
                'id_rol'        => 2,
                'estado'        => 'ACTIVO',
            ]);
        }

        // Asegurar que la materia está asignada en docente_asignatura
        DB::table('docente_asignatura')->updateOrInsert(
            [
                'id_usuario'    => $docente->id_usuario,
                'id_carrera'    => $idCarrera,
                'id_asignatura' => $idAsignatura,
            ],
            [
                'fecha_asignacion' => now(),
            ]
        );

        // 5 Estudiantes de Informática para Alan
        $estudiantesData = [
            ['ci' => '8200001', 'codigo_universitario' => '20240011', 'nombre' => 'Fernando', 'apellido' => 'Claros',     'correo' => 'fernando.claros@est.umss.edu'],
            ['ci' => '8200002', 'codigo_universitario' => '20240012', 'nombre' => 'Paola',    'apellido' => 'Zeballos',   'correo' => 'paola.zeballos@est.umss.edu'],
            ['ci' => '8200003', 'codigo_universitario' => '20240013', 'nombre' => 'Sergio',   'apellido' => 'Montaño',    'correo' => 'sergio.montano@est.umss.edu'],
            ['ci' => '8200004', 'codigo_universitario' => '20240014', 'nombre' => 'Andrea',   'apellido' => 'Choque',     'correo' => 'andrea.choque@est.umss.edu'],
            ['ci' => '8200005', 'codigo_universitario' => '20240015', 'nombre' => 'Mauricio', 'apellido' => 'Villarroel', 'correo' => 'mauricio.villarroel@est.umss.edu'],
        ];

        $estudiantesIds = [];

        foreach ($estudiantesData as $item) {
            $estudiante = Estudiante::updateOrCreate(
                ['ci' => $item['ci']],
                [
                    'codigo_universitario' => $item['codigo_universitario'],
                    'nombre'               => $item['nombre'],
                    'apellido'             => $item['apellido'],
                    'correo'               => $item['correo'],
                    'id_carrera'           => $idCarrera,
                    'estado'               => 'ACTIVO',
                ]
            );

            // Inscribir a ALGORITMOS AVANZADOS
            $estudiante->asignaturas()->syncWithoutDetaching([$idAsignatura]);
            $estudiantesIds[] = $estudiante->id_estudiante;
        }

        // Crear Examen para ALGORITMOS AVANZADOS
        $examen = Examen::updateOrCreate(
            [
                'id_asignatura' => $idAsignatura,
                'nombre'        => 'Primer Parcial - Algoritmos Avanzados (Docente Alan)',
            ],
            [
                'fecha'            => now()->addDays(4)->format('Y-m-d'),
                'hora_inicio'      => '10:00:00',
                'duracion_minutos' => 120,
                'descripcion'      => 'Examen parcial oficial asignado a la materia del docente Alan Peralta.',
                'estado'           => 'PROGRAMADO',
            ]
        );

        // Asignar ambiente (Laboratorio 102)
        AsignacionAmbiente::updateOrCreate(
            [
                'id_examen'   => $examen->id_examen,
                'id_ambiente' => 2,
            ]
        );

        // Habilitar a los 5 estudiantes en el examen
        foreach ($estudiantesIds as $idEst) {
            Habilitacion::updateOrCreate(
                [
                    'id_estudiante' => $idEst,
                    'id_examen'     => $examen->id_examen,
                ],
                [
                    'estado' => 'HABILITADO',
                    'motivo' => null,
                ]
            );
        }
    }
}
