<?php

namespace Database\Seeders;

use App\Models\AsignacionAmbiente;
use App\Models\Estudiante;
use App\Models\Examen;
use App\Models\Habilitacion;
use App\Models\Rol;
use App\Models\Usuario;
use App\Utilities\PasswordHasher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DocenteSebasSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Obtener o verificar Rol DOCENTE
        $rolDocente = Rol::where('nombre', 'DOCENTE')->first()
            ?? Rol::create(['nombre' => 'DOCENTE', 'descripcion' => 'Docente titular']);

        // 2. Crear o actualizar Usuario "sebas"
        $docente = Usuario::updateOrCreate(
            ['username' => 'sebas'],
            [
                'nombre'        => 'Sebastian',
                'apellido'      => 'Quiroga',
                'correo'        => 'sebas@umss.edu.bo',
                'password_hash' => PasswordHasher::hash('sebas123'),
                'id_rol'        => $rolDocente->id_rol,
                'estado'        => 'ACTIVO',
            ]
        );

        $idCarrera = 1;     // Ingeniería de Sistemas
        $idAsignatura = 1;  // INGENIERIA DE SOFTWARE

        // 3. Asignar materia a Sebas en docente_asignatura
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

        // 4. Crear los 10 estudiantes de prueba
        $estudiantesData = [
            ['ci' => '8100001', 'codigo_universitario' => '20240001', 'nombre' => 'Alejandro', 'apellido' => 'Torrico', 'correo' => 'alejandro.torrico@est.umss.edu'],
            ['ci' => '8100002', 'codigo_universitario' => '20240002', 'nombre' => 'Camila',    'apellido' => 'Vargas',  'correo' => 'camila.vargas@est.umss.edu'],
            ['ci' => '8100003', 'codigo_universitario' => '20240003', 'nombre' => 'Rodrigo',   'apellido' => 'Mamani',  'correo' => 'rodrigo.mamani@est.umss.edu'],
            ['ci' => '8100004', 'codigo_universitario' => '20240004', 'nombre' => 'Lucia',     'apellido' => 'Flores',  'correo' => 'lucia.flores@est.umss.edu'],
            ['ci' => '8100005', 'codigo_universitario' => '20240005', 'nombre' => 'Gabriel',   'apellido' => 'Fernandez','correo' => 'gabriel.fernandez@est.umss.edu'],
            ['ci' => '8100006', 'codigo_universitario' => '20240006', 'nombre' => 'Sofia',     'apellido' => 'Rojas',   'correo' => 'sofia.rojas@est.umss.edu'],
            ['ci' => '8100007', 'codigo_universitario' => '20240007', 'nombre' => 'Mateo',     'apellido' => 'Gutierrez','correo' => 'mateo.gutierrez@est.umss.edu'],
            ['ci' => '8100008', 'codigo_universitario' => '20240008', 'nombre' => 'Valeria',   'apellido' => 'Morales', 'correo' => 'valeria.morales@est.umss.edu'],
            ['ci' => '8100009', 'codigo_universitario' => '20240009', 'nombre' => 'Diego',     'apellido' => 'Paredes', 'correo' => 'diego.paredes@est.umss.edu'],
            ['ci' => '8100010', 'codigo_universitario' => '20240010', 'nombre' => 'Natalia',   'apellido' => 'Mendoza', 'correo' => 'natalia.mendoza@est.umss.edu'],
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

            // Inscribir estudiante a la materia
            $estudiante->asignaturas()->syncWithoutDetaching([$idAsignatura]);
            $estudiantesIds[] = $estudiante->id_estudiante;
        }

        // 5. Crear Examen para la materia del docente
        $examen = Examen::updateOrCreate(
            [
                'id_asignatura' => $idAsignatura,
                'nombre'        => 'Primer Parcial - Ing. de Software (Docente Sebas)',
            ],
            [
                'fecha'            => now()->addDays(3)->format('Y-m-d'),
                'hora_inicio'      => '09:00:00',
                'duracion_minutos' => 90,
                'descripcion'      => 'Examen parcial oficial asignado a la materia del docente Sebastian.',
                'estado'           => 'PROGRAMADO',
            ]
        );

        // Asignar ambiente (Laboratorio 101)
        AsignacionAmbiente::updateOrCreate(
            [
                'id_examen'   => $examen->id_examen,
                'id_ambiente' => 1,
            ]
        );

        // Habilitar a los 10 estudiantes en el examen
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
