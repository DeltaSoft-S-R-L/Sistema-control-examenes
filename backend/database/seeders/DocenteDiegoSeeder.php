<?php

namespace Database\Seeders;

use App\Models\Ambiente;
use App\Models\AsignacionAmbiente;
use App\Models\Estudiante;
use App\Models\Examen;
use App\Models\Habilitacion;
use App\Models\Rol;
use App\Models\Usuario;
use App\Utilities\PasswordHasher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DocenteDiegoSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Obtener o verificar Rol DOCENTE
        $rolDocente = Rol::where('nombre', 'DOCENTE')->first()
            ?? Rol::create(['nombre' => 'DOCENTE', 'descripcion' => 'Docente titular']);

        // 2. Crear o actualizar Usuario "Diego"
        $docente = Usuario::updateOrCreate(
            ['username' => 'Diego'],
            [
                'nombre'        => 'Diego',
                'apellido'      => 'Revollo',
                'correo'        => '202300871@umss.edu.bo',
                'password_hash' => PasswordHasher::hash('diego123'),
                'id_rol'        => $rolDocente->id_rol,
                'estado'        => 'ACTIVO',
            ]
        );

        $idCarrera = 1;    // Ingeniería de Sistemas
        $idAsignatura = 6; // INTRODUCCION A LA PROGRAMACION

        // 3. Asignar materia a Diego en docente_asignatura
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

        // 4. Crear los estudiantes de prueba para la materia de Diego
        $estudiantesData = [
            [
                'ci'                   => '8300001',
                'codigo_universitario' => '20240021',
                'nombre'               => 'Carlos',
                'apellido'             => 'Arce',
                'correo'               => 'carlos.arce@est.umss.edu',
                'estado_hab'           => 'HABILITADO',
                'motivo_tipo'          => null,
                'motivo_detalle'       => null,
                'ambiente_codigo'      => 'A101',
            ],
            [
                'ci'                   => '8300002',
                'codigo_universitario' => '20240022',
                'nombre'               => 'Mariana',
                'apellido'             => 'Rios',
                'correo'               => 'mariana.rios@est.umss.edu',
                'estado_hab'           => 'HABILITADO',
                'motivo_tipo'          => null,
                'motivo_detalle'       => null,
                'ambiente_codigo'      => 'A101',
            ],
            [
                'ci'                   => '8300003',
                'codigo_universitario' => '20240023',
                'nombre'               => 'Lucas',
                'apellido'             => 'Salazar',
                'correo'               => 'lucas.salazar@est.umss.edu',
                'estado_hab'           => 'HABILITADO',
                'motivo_tipo'          => null,
                'motivo_detalle'       => null,
                'ambiente_codigo'      => 'A102',
            ],
            [
                'ci'                   => '8300004',
                'codigo_universitario' => '20240024',
                'nombre'               => 'Elena',
                'apellido'             => 'Guzman',
                'correo'               => 'elena.guzman@est.umss.edu',
                'estado_hab'           => 'HABILITADO',
                'motivo_tipo'          => null,
                'motivo_detalle'       => null,
                'ambiente_codigo'      => 'A102',
            ],
            [
                'ci'                   => '8300005',
                'codigo_universitario' => '20240025',
                'nombre'               => 'Javier',
                'apellido'             => 'Orellana',
                'correo'               => 'javier.orellana@est.umss.edu',
                'estado_hab'           => 'HABILITADO',
                'motivo_tipo'          => null,
                'motivo_detalle'       => null,
                'ambiente_codigo'      => null, // Sin ambiente específico asignado (acceso libre)
            ],
            [
                'ci'                   => '8300006',
                'codigo_universitario' => '20240026',
                'nombre'               => 'Daniela',
                'apellido'             => 'Canedo',
                'correo'               => 'daniela.canedo@est.umss.edu',
                'estado_hab'           => 'HABILITADO',
                'motivo_tipo'          => null,
                'motivo_detalle'       => null,
                'ambiente_codigo'      => null, // Sin ambiente específico asignado (acceso libre)
            ],
            [
                'ci'                   => '8300007',
                'codigo_universitario' => '20240027',
                'nombre'               => 'Roberto',
                'apellido'             => 'Soliz',
                'correo'               => 'roberto.soliz@est.umss.edu',
                'estado_hab'           => 'NO_HABILITADO',
                'motivo_tipo'          => 'DOCUMENTACION',
                'motivo_detalle'       => 'Pendiente de regularización de matrícula institucional.',
                'ambiente_codigo'      => null,
            ],
            [
                'ci'                   => '8300008',
                'codigo_universitario' => '20240028',
                'nombre'               => 'Claudia',
                'apellido'             => 'Beltran',
                'correo'               => 'claudia.beltran@est.umss.edu',
                'estado_hab'           => 'NO_HABILITADO',
                'motivo_tipo'          => 'ACADEMICO',
                'motivo_detalle'       => 'Inhabilitado por superar el límite del 20% de inasistencias a clases teóricas.',
                'ambiente_codigo'      => null,
            ],
        ];

        // 5. Crear Examen para la materia del docente Diego
        $examen = Examen::updateOrCreate(
            [
                'id_asignatura' => $idAsignatura,
                'nombre'        => 'Primer Parcial - Introducción a la Programación (Docente Diego)',
            ],
            [
                'fecha'            => now()->addDays(2)->format('Y-m-d'),
                'hora_inicio'      => '08:30:00',
                'duracion_minutos' => 90,
                'descripcion'      => 'Examen parcial oficial asignado a la materia del docente Diego Revollo.',
                'estado'           => 'PROGRAMADO',
            ]
        );

        // 6. Asignar ambientes al examen (Laboratorio 101 y Laboratorio 102)
        $amb101 = Ambiente::where('codigo', 'A101')->first();
        $amb102 = Ambiente::where('codigo', 'A102')->first();

        $asig101 = $amb101 ? AsignacionAmbiente::updateOrCreate(
            [
                'id_examen'   => $examen->id_examen,
                'id_ambiente' => $amb101->id_ambiente,
            ]
        ) : null;

        $asig102 = $amb102 ? AsignacionAmbiente::updateOrCreate(
            [
                'id_examen'   => $examen->id_examen,
                'id_ambiente' => $amb102->id_ambiente,
            ]
        ) : null;

        $asigMap = [
            'A101' => $asig101?->id_asignacion_ambiente,
            'A102' => $asig102?->id_asignacion_ambiente,
        ];

        // 7. Crear los estudiantes, inscribirlos a la materia y habilitarlos en el examen
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

            // Determinar asignación de ambiente
            $idAsigAmbiente = $item['ambiente_codigo'] && isset($asigMap[$item['ambiente_codigo']])
                ? $asigMap[$item['ambiente_codigo']]
                : null;

            $esInhabilitado = $item['estado_hab'] === 'NO_HABILITADO';

            // Habilitación o inhabilitación
            Habilitacion::updateOrCreate(
                [
                    'id_estudiante' => $estudiante->id_estudiante,
                    'id_examen'     => $examen->id_examen,
                ],
                [
                    'id_asignacion_ambiente' => $idAsigAmbiente,
                    'estado'                 => $item['estado_hab'],
                    'motivo'                 => $esInhabilitado ? $item['motivo_detalle'] : null,
                    'motivo_tipo'            => $esInhabilitado ? $item['motivo_tipo'] : null,
                    'motivo_detalle'         => $esInhabilitado ? $item['motivo_detalle'] : null,
                    'inhabilitado_por'       => $esInhabilitado ? $docente->id_usuario : null,
                    'inhabilitado_at'        => $esInhabilitado ? now() : null,
                ]
            );
        }

        // 8. Crear Normas de Examen (Generales y Particulares)
        \App\Models\NormaExamen::updateOrCreate(
            [
                'id_examen'   => $examen->id_examen,
                'tipo'        => 'GENERAL',
                'descripcion' => 'Portar documento de identidad (CI) o credencial universitaria física o digital.',
            ],
            [
                'id_estudiante' => null,
                'created_by'    => $docente->id_usuario,
            ]
        );

        \App\Models\NormaExamen::updateOrCreate(
            [
                'id_examen'   => $examen->id_examen,
                'tipo'        => 'GENERAL',
                'descripcion' => 'Prohibido el uso de teléfonos celulares, relojes inteligentes u otros dispositivos de comunicación durante la prueba.',
            ],
            [
                'id_estudiante' => null,
                'created_by'    => $docente->id_usuario,
            ]
        );

        $primerEstudiante = Estudiante::where('ci', '8300001')->first();
        if ($primerEstudiante) {
            \App\Models\NormaExamen::updateOrCreate(
                [
                    'id_examen'     => $examen->id_examen,
                    'id_estudiante' => $primerEstudiante->id_estudiante,
                    'tipo'          => 'PARTICULAR',
                ],
                [
                    'descripcion' => 'Estudiante autorizado a utilizar calculadora científica básica.',
                    'created_by'  => $docente->id_usuario,
                ]
            );
        }
    }
}
