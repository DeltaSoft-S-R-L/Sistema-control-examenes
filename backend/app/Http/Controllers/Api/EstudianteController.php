<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asignatura;
use App\Models\Carrera;
use App\Models\Estudiante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EstudianteController extends Controller
{


private function esAdministrador(Request $request): bool
{
    return strtoupper($request->user()->rol?->nombre ?? '') === 'ADMINISTRADOR';
}

public function index(Request $request)
{
    $usuario = $request->user();
    $rol = strtoupper($usuario->rol?->nombre ?? '');

    if (!in_array($rol, ['ADMINISTRADOR', 'DOCENTE'], true)) {
        return response()->json([
            'message' => 'No tienes permiso para consultar estudiantes.',
        ], 403);
    }

    $query = Estudiante::with(['carrera', 'asignaturas']);

    if ($rol === 'DOCENTE') {
        $query->whereExists(function ($subquery) use ($usuario) {
            $subquery->selectRaw('1')
                ->from('estudiante_asignatura as ea')
                ->join('docente_asignatura as da', function ($join) {
                    $join->on(
                        'da.id_asignatura',
                        '=',
                        'ea.id_asignatura'
                    )->on(
                        'da.id_carrera',
                        '=',
                        'estudiante.id_carrera'
                    );
                })
                ->whereColumn(
                    'ea.id_estudiante',
                    'estudiante.id_estudiante'
                )
                ->where('da.id_usuario', $usuario->id_usuario);
        });
    }

    
    // Filtrar estudiantes por una materia específica.
    if ($request->filled('id_asignatura')) {
        $datosFiltro = $request->validate([
            'id_asignatura' => [
                'required',
                'integer',
                'exists:asignatura,id_asignatura',
            ],
        ]);

        $idAsignatura = (int) $datosFiltro['id_asignatura'];

        $query->whereHas('asignaturas', function ($q) use ($idAsignatura) {
            $q->where('asignatura.id_asignatura', $idAsignatura);
        });

        // Para docentes, comprobar también la carrera
        // y que tengan asignada esta materia.
        if ($rol === 'DOCENTE') {
            $query->whereExists(function ($subquery) use ($usuario, $idAsignatura) {
                $subquery->selectRaw('1')
                    ->from('docente_asignatura as da')
                    ->whereColumn(
                        'da.id_carrera',
                        'estudiante.id_carrera'
                    )
                    ->where('da.id_usuario', $usuario->id_usuario)
                    ->where('da.id_asignatura', $idAsignatura);
            });
        }
    }

    if ($request->filled('estado')) {
        $query->where('estado', $request->estado);
    }

    if ($request->filled('buscar')) {
        $buscar = $request->buscar;

        $query->where(function ($q) use ($buscar) {
            $q->where('nombre', 'ilike', "%{$buscar}%")
                ->orWhere('apellido', 'ilike', "%{$buscar}%")
                ->orWhere('ci', 'ilike', "%{$buscar}%")
                ->orWhere('codigo_universitario', 'ilike', "%{$buscar}%");
        });
    }


$estudiantes = $query->orderBy('apellido')->paginate(20);

if ($rol === 'DOCENTE') {
    $asignaciones = DB::table('docente_asignatura')
        ->where('id_usuario', $usuario->id_usuario)
        ->get(['id_carrera', 'id_asignatura']);

    $materiasPorCarrera = $asignaciones
        ->groupBy('id_carrera')
        ->map(fn ($filas) => $filas->pluck('id_asignatura')->all());

    $estudiantes->getCollection()->each(
        function ($estudiante) use ($materiasPorCarrera) {
            $idsPermitidos = $materiasPorCarrera
                ->get($estudiante->id_carrera, []);

            $estudiante->setRelation(
                'asignaturas',
                $estudiante->asignaturas
                    ->whereIn('id_asignatura', $idsPermitidos)
                    ->values()
            );
        }
    );
}

return response()->json($estudiantes);

}


    public function store(Request $request)
    {

if (!$this->esAdministrador($request)) {
    return response()->json([
        'message' => 'Solo el administrador puede modificar estudiantes.'
    ], 403);
}

        $data = $request->validate([
            'ci'                   => 'required|string|max:20|unique:estudiante,ci',
            'nombre'               => 'required|string|max:100',
            'apellido'             => 'required|string|max:100',
            'codigo_universitario' => 'required|string|max:50|unique:estudiante,codigo_universitario',
            'id_carrera'           => 'required|integer|exists:carrera,id_carrera',
            'asignaturas'          => 'required|array|min:1',
            'asignaturas.*'        => 'integer|distinct|exists:asignatura,id_asignatura',
            'correo'               => 'nullable|email|max:150',
            'estado'               => [
                'required',
                \Illuminate\Validation\Rule::in([
                    'ACTIVO',
                    'INACTIVO',
                    'activo',
                    'inactivo',
                ]),
            ],
        ]);

        $data['estado'] = strtoupper($data['estado']);

$asignaturas = $data['asignaturas'];
unset($data['asignaturas']);

$cantidadAsignaturasValidas = Asignatura::whereIn(
    'id_asignatura',
    $asignaturas
)
    ->whereHas('carreras', function ($query) use ($data) {
        $query->where('carrera.id_carrera', $data['id_carrera']);
    })
    ->count();

if ($cantidadAsignaturasValidas !== count($asignaturas)) {
    return response()->json([
        'message' => 'Una o más asignaturas no pertenecen a la carrera seleccionada.',
        'errors' => [
            'asignaturas' => [
                'Todas las asignaturas deben pertenecer a la carrera seleccionada.',
            ],
        ],
    ], 422);
}

$estudiante = Estudiante::create($data);

        $estudiante->asignaturas()->sync($asignaturas);

        $estudiante->load([
        'carrera',
        'asignaturas',
        ]);

        return response()->json($estudiante, 201);
    }

    public function importar(Request $request)
{

$usuario = $request->user();
$rol = strtoupper($usuario->rol?->nombre ?? '');

if (!in_array($rol, ['ADMINISTRADOR', 'DOCENTE'], true)) {
    return response()->json([
        'message' => 'No tienes permiso para importar estudiantes.'
    ], 403);
}

    $request->validate([
        'archivo' => 'required|file|mimes:csv,txt|max:2048',
    ]);

    $archivo = $request->file('archivo');
    $handle = fopen($archivo->getRealPath(), 'r');

    if ($handle === false) {
        return response()->json([
            'message' => 'No se pudo leer el archivo CSV.',
        ], 422);
    }

    // Detectar automáticamente si el CSV usa coma (,) o punto y coma (;).
    $primeraLinea = fgets($handle);

    if ($primeraLinea === false) {
        fclose($handle);

        return response()->json([
            'message' => 'El archivo CSV está vacío.',
        ], 422);
    }

    $cantidadComas = substr_count($primeraLinea, ',');
    $cantidadPuntoComas = substr_count($primeraLinea, ';');

    $delimitador = $cantidadPuntoComas > $cantidadComas ? ';' : ',';

    rewind($handle);

    $encabezados = fgetcsv(
        $handle,
        null,
        $delimitador,
        '"',
        '\\'
    );

    if (!$encabezados) {
        fclose($handle);

        return response()->json([
            'message' => 'El archivo CSV está vacío.',
        ], 422);
    }

    $encabezados = array_map('trim', $encabezados);

    $encabezados[0] = preg_replace(
        '/^\xEF\xBB\xBF/',
        '',
        $encabezados[0]
    );

    while (
        count($encabezados) > 0 &&
        trim((string) end($encabezados)) === ''
    ) {
        array_pop($encabezados);
    }

    $columnasEsperadas = [
        'ci',
        'codigo_universitario',
        'nombre',
        'apellido',
        'correo',
        'estado',
        'carrera',
        'asignaturas',
    ];

    if ($encabezados !== $columnasEsperadas) {
        fclose($handle);

        return response()->json([
            'message' => 'El formato del archivo CSV no es válido.',
            'columnas_esperadas' => $columnasEsperadas,
        ], 422);
    }

    $importados = 0;
    $errores = [];
    $fila = 1;

    while (
        ($datos = fgetcsv(
            $handle,
            null,
            $delimitador,
            '"',
            '\\'
        )) !== false
    ) {
        $fila++;

        while (
            count($datos) > 0 &&
            trim((string) end($datos)) === ''
        ) {
            array_pop($datos);
        }

        // Ignorar filas completamente vacías.
        if (
            count(array_filter(
                $datos,
                fn ($valor) => trim((string) $valor) !== ''
            )) === 0
        ) {
            continue;
        }

        if (count($datos) !== count($columnasEsperadas)) {
            $errores[] = [
                'fila' => $fila,
                'mensaje' => 'La cantidad de columnas no es válida.',
            ];

            continue;
        }

        $datos = array_map(function ($valor) {
            $valor = trim($valor);

            if (!mb_check_encoding($valor, 'UTF-8')) {
                $valor = mb_convert_encoding(
                    $valor,
                    'UTF-8',
                    'Windows-1252'
                );
            }

            return $valor;
        }, $datos);

        $estudiante = array_combine(
            $columnasEsperadas,
            $datos
        );

        $estudiante['estado'] = strtoupper(
            $estudiante['estado']
        );

        $validator = validator($estudiante, [
            'ci' => 'required|string|max:20',
            'codigo_universitario' => 'required|string|max:50',
            'nombre' => 'required|string|max:100',
            'apellido' => 'required|string|max:100',
            'correo' => 'nullable|email|max:150',
            'estado' => 'required|in:ACTIVO,INACTIVO',
            'carrera' => 'required|string|max:30',
            'asignaturas' => 'required|string',
        ]);

        if ($validator->fails()) {
            $errores[] = [
                'fila' => $fila,
                'mensaje' => $validator->errors()->first(),
            ];

            continue;
        }

        // Buscar la carrera por su código.
        $carrera = Carrera::where(
            'codigo',
            $estudiante['carrera']
        )->first();

        if (!$carrera) {
            $errores[] = [
                'fila' => $fila,
                'mensaje' => "La carrera {$estudiante['carrera']} no existe.",
            ];

            continue;
        }

        // Las asignaturas se escriben separadas por |.
        $codigosAsignaturas = array_values(array_unique(
            array_filter(
                array_map(
                    'trim',
                    explode('|', $estudiante['asignaturas'])
                )
            )
        ));

        if (count($codigosAsignaturas) === 0) {
            $errores[] = [
                'fila' => $fila,
                'mensaje' => 'Debe indicar al menos una asignatura.',
            ];

            continue;
        }

        $asignaturas = Asignatura::whereIn(
    'codigo',
    $codigosAsignaturas
    )
    ->whereHas('carreras', function ($query) use ($carrera) {
        $query->where('carrera.id_carrera', $carrera->id_carrera);
    })
    ->get();

        if ($asignaturas->count() !== count($codigosAsignaturas)) {
            $codigosEncontrados = $asignaturas
                ->pluck('codigo')
                ->all();

            $codigosNoEncontrados = array_values(
                array_diff(
                    $codigosAsignaturas,
                    $codigosEncontrados
                )
            );

            $errores[] = [
    'fila' => $fila,
    'mensaje' => 'Las siguientes asignaturas no existen o no pertenecen a la carrera ' .
        $carrera->codigo . ': ' .
        implode(', ', $codigosNoEncontrados) . '.',
];

            continue;
        }


if ($rol === 'DOCENTE') {
    $idsAsignaturas = $asignaturas
        ->pluck('id_asignatura')
        ->all();

    $cantidadAutorizada = DB::table('docente_asignatura')
        ->where('id_usuario', $usuario->id_usuario)
        ->where('id_carrera', $carrera->id_carrera)
        ->whereIn('id_asignatura', $idsAsignaturas)
        ->distinct()
        ->count('id_asignatura');

    if ($cantidadAutorizada !== count($idsAsignaturas)) {
        $errores[] = [
            'fila' => $fila,
            'mensaje' => 'No tienes autorización para importar estudiantes en una o más materias de esta carrera.',
        ];

        continue;
    }
}


$porCi = Estudiante::where('ci', $estudiante['ci'])->first();

$porCodigo = Estudiante::where(
    'codigo_universitario',
    $estudiante['codigo_universitario']
)->first();

// Si CI y código apuntan a personas diferentes, rechazar.
if (
    $porCi &&
    $porCodigo &&
    $porCi->id_estudiante !== $porCodigo->id_estudiante
) {
    $errores[] = [
        'fila' => $fila,
        'mensaje' => 'El CI y el código universitario pertenecen a estudiantes diferentes.',
    ];

    continue;
}

// Si existe por un identificador, ambos deben coincidir.
$estudianteExistente = $porCi ?? $porCodigo;

if (
    $estudianteExistente &&
    (
        $estudianteExistente->ci !== $estudiante['ci'] ||
        $estudianteExistente->codigo_universitario !== $estudiante['codigo_universitario']
    )
) {
    $errores[] = [
        'fila' => $fila,
        'mensaje' => 'El CI o el código universitario no coincide con el estudiante registrado.',
    ];

    continue;
}

// No agregar materias de otra carrera al estudiante existente.
if (
    $estudianteExistente &&
    (int) $estudianteExistente->id_carrera !== (int) $carrera->id_carrera
) {
    $errores[] = [
        'fila' => $fila,
        'mensaje' => 'La carrera del CSV no coincide con la carrera registrada del estudiante.',
    ];

    continue;
}


try {
    DB::transaction(function () use (
        $estudianteExistente,
        $estudiante,
        $carrera,
        $asignaturas
    ) {
        if ($estudianteExistente) {
            // Conservar los datos del estudiante existente.
            $estudianteGuardado = $estudianteExistente;
        } else {
            // Crear un estudiante únicamente si no existe.
            $estudianteGuardado = Estudiante::create([
                'ci' => $estudiante['ci'],
                'codigo_universitario' => $estudiante['codigo_universitario'],
                'nombre' => $estudiante['nombre'],
                'apellido' => $estudiante['apellido'],
                'id_carrera' => $carrera->id_carrera,
                'correo' => $estudiante['correo'] ?: null,
                'estado' => $estudiante['estado'],
            ]);
        }

        // Agregar materias sin eliminar las anteriores.
        $estudianteGuardado->asignaturas()->syncWithoutDetaching(
            $asignaturas->pluck('id_asignatura')->all()
        );
    });

    $importados++;
} catch (\Throwable $e) {
    report($e);

    $errores[] = [
        'fila' => $fila,
        'mensaje' => 'No se pudo guardar el estudiante y sus materias.',
    ];
}


    }

    fclose($handle);

    return response()->json([
        'message' => 'Proceso de importación finalizado.',
        'importados' => $importados,
        'errores' => $errores,
    ]);
}


public function show(Request $request, string $id)
{
    $usuario = $request->user();
    $rol = strtoupper($usuario->rol?->nombre ?? '');

    if (!in_array($rol, ['ADMINISTRADOR', 'DOCENTE'], true)) {
        return response()->json([
            'message' => 'No tienes permiso para consultar estudiantes.',
        ], 403);
    }

    $query = Estudiante::query();

    if ($rol === 'DOCENTE') {
        $query->whereExists(function ($subquery) use ($usuario) {
            $subquery->selectRaw('1')
                ->from('estudiante_asignatura as ea')
                ->join('docente_asignatura as da', function ($join) {
                    $join->on(
                        'da.id_asignatura',
                        '=',
                        'ea.id_asignatura'
                    )->on(
                        'da.id_carrera',
                        '=',
                        'estudiante.id_carrera'
                    );
                })
                ->whereColumn(
                    'ea.id_estudiante',
                    'estudiante.id_estudiante'
                )
                ->where('da.id_usuario', $usuario->id_usuario);
        });
    }



$relaciones = [
    'carrera',
    'asignaturas',
];

if ($rol === 'DOCENTE') {
    $relaciones['habilitaciones'] = function ($queryHabilitaciones) use ($usuario) {
        $queryHabilitaciones->whereHas(
            'examen',
            function ($queryExamenes) use ($usuario) {
                $queryExamenes->whereExists(
                    function ($subquery) use ($usuario) {
                        $subquery->selectRaw('1')
                            ->from('docente_asignatura as da')
                            ->join('estudiante as e', function ($join) {
                                $join->on(
                                    'e.id_carrera',
                                    '=',
                                    'da.id_carrera'
                                );
                            })
                            ->whereColumn(
                                'e.id_estudiante',
                                'habilitacion.id_estudiante'
                            )
                            ->whereColumn(
                                'da.id_asignatura',
                                'examen.id_asignatura'
                            )
                            ->where(
                                'da.id_usuario',
                                $usuario->id_usuario
                            );
                    }
                );
            }
        )->with('examen');
    };
} else {
    $relaciones[] = 'habilitaciones.examen';
}

$estudiante = $query->with($relaciones)->findOrFail($id);


if ($rol === 'DOCENTE') {
    $idsPermitidos = DB::table('docente_asignatura')
        ->where('id_usuario', $usuario->id_usuario)
        ->where('id_carrera', $estudiante->id_carrera)
        ->pluck('id_asignatura')
        ->all();

    $estudiante->setRelation(
        'asignaturas',
        $estudiante->asignaturas
            ->whereIn('id_asignatura', $idsPermitidos)
            ->values()
    );
}

return response()->json($estudiante);

}


    public function update(Request $request, string $id)
    {

if (!$this->esAdministrador($request)) {
    return response()->json([
        'message' => 'Solo el administrador puede modificar estudiantes.'
    ], 403);
}

        $estudiante = Estudiante::findOrFail($id);

        $data = $request->validate([
            'ci'                   => "sometimes|required|string|max:20|unique:estudiante,ci,{$id},id_estudiante",
            'nombre'               => 'sometimes|required|string|max:100',
            'apellido'             => 'sometimes|required|string|max:100',
            'codigo_universitario' => "sometimes|required|string|max:50|unique:estudiante,codigo_universitario,{$id},id_estudiante",
            'id_carrera'           => 'sometimes|required|integer|exists:carrera,id_carrera',
            'asignaturas'          => 'sometimes|required|array|min:1',
            'asignaturas.*'        => 'integer|distinct|exists:asignatura,id_asignatura',
            'correo'               => 'nullable|email|max:150',
            'estado'               => 'sometimes|required|in:ACTIVO,INACTIVO,activo,inactivo',
        ]);

        if (isset($data['estado'])) {
        $data['estado'] = strtoupper($data['estado']);
        }

        $asignaturas = $data['asignaturas'] ?? null;
unset($data['asignaturas']);

$idCarrera = $data['id_carrera'] ?? $estudiante->id_carrera;

if ($asignaturas === null && isset($data['id_carrera'])) {
    $asignaturas = $estudiante->asignaturas()
        ->pluck('asignatura.id_asignatura')
        ->all();
}

if ($asignaturas !== null) {
    $cantidadAsignaturasValidas = Asignatura::whereIn(
        'id_asignatura',
        $asignaturas
    )
        ->whereHas('carreras', function ($query) use ($idCarrera) {
            $query->where('carrera.id_carrera', $idCarrera);
        })
        ->count();

    if ($cantidadAsignaturasValidas !== count($asignaturas)) {
        return response()->json([
            'message' => 'Una o más asignaturas no pertenecen a la carrera seleccionada.',
            'errors' => [
                'asignaturas' => [
                    'Todas las asignaturas deben pertenecer a la carrera seleccionada.',
                ],
            ],
        ], 422);
    }
}

$estudiante->update($data);

if ($asignaturas !== null) {
    $estudiante->asignaturas()->sync($asignaturas);
}

    $estudiante->load([
        'carrera',
        'asignaturas',
    ]);

        return response()->json($estudiante);
    }

public function destroy(Request $request, string $id)
{
    if (!$this->esAdministrador($request)) {
        return response()->json([
            'message' => 'Solo el administrador puede eliminar estudiantes.'
        ], 403);
    }

    $estudiante = Estudiante::findOrFail($id);

    $estudiante->delete();

    return response()->json([
        'message' => 'Estudiante eliminado.'
    ]);
}

}
