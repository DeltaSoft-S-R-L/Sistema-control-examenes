<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asignatura;
use App\Models\Carrera;
use App\Models\Estudiante;
use Illuminate\Http\Request;

class EstudianteController extends Controller
{
    public function index(Request $request)
    {
        $query = Estudiante::with([
        'carrera',
        'asignaturas',
        ]);

        if ($request->filled('estado') && is_string($request->input('estado'))) {
            $query->where('estado', strtoupper(trim($request->input('estado'))));
        }
        if ($request->has('buscar')) {
            $q = $request->buscar;
            $query->where(function ($q2) use ($q) {
                $q2->where('nombre', 'ilike', "%{$q}%")
                    ->orWhere('apellido', 'ilike', "%{$q}%")
                    ->orWhere('ci', 'ilike', "%{$q}%")
                    ->orWhere('codigo_universitario', 'ilike', "%{$q}%");
            });
        }

        return response()->json($query->orderBy('apellido')->paginate(20));
    }

    public function store(Request $request)
    {
        $this->normalizarEstado($request);

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
                ]),
            ],
        ]);

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
            'ci' => 'required|string|max:20|unique:estudiante,ci',
            'codigo_universitario' => 'required|string|max:50|unique:estudiante,codigo_universitario',
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

        $nuevoEstudiante = Estudiante::create([
            'ci' => $estudiante['ci'],
            'codigo_universitario' => $estudiante['codigo_universitario'],
            'nombre' => $estudiante['nombre'],
            'apellido' => $estudiante['apellido'],
            'id_carrera' => $carrera->id_carrera,
            'correo' => $estudiante['correo'] ?: null,
            'estado' => $estudiante['estado'],
        ]);

        $nuevoEstudiante->asignaturas()->sync(
            $asignaturas->pluck('id_asignatura')->all()
        );

        $importados++;
    }

    fclose($handle);

    return response()->json([
        'message' => 'Proceso de importación finalizado.',
        'importados' => $importados,
        'errores' => $errores,
    ]);
}

    public function show(string $id)
    {
        $estudiante = Estudiante::with([
            'carrera',
            'asignaturas',
            'habilitaciones.examen',
        ])->findOrFail($id);

        return response()->json($estudiante);
    }

    public function update(Request $request, string $id)
    {
        $estudiante = Estudiante::findOrFail($id);

        $this->normalizarEstado($request);

        $data = $request->validate([
            'ci'                   => "sometimes|required|string|max:20|unique:estudiante,ci,{$id},id_estudiante",
            'nombre'               => 'sometimes|required|string|max:100',
            'apellido'             => 'sometimes|required|string|max:100',
            'codigo_universitario' => "sometimes|required|string|max:50|unique:estudiante,codigo_universitario,{$id},id_estudiante",
            'id_carrera'           => 'sometimes|required|integer|exists:carrera,id_carrera',
            'asignaturas'          => 'sometimes|required|array|min:1',
            'asignaturas.*'        => 'integer|distinct|exists:asignatura,id_asignatura',
            'correo'               => 'nullable|email|max:150',
            'estado'               => 'sometimes|required|in:ACTIVO,INACTIVO',
        ]);

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

    private function normalizarEstado(Request $request): void
    {
        if ($request->has('estado') && is_string($request->input('estado'))) {
            $request->merge([
                'estado' => strtoupper(trim($request->input('estado'))),
            ]);
        }
    }
    public function destroy(string $id)
    {
        $estudiante = Estudiante::findOrFail($id);
        $estudiante->delete();

        return response()->json(['message' => 'Estudiante eliminado.']);
    }
}
