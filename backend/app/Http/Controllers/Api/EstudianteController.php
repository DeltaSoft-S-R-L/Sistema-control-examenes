<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Estudiante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use League\Csv\Reader;

class EstudianteController extends Controller
{
    // ─────────────────────────────────────────────
    // GET /api/estudiantes
    // ─────────────────────────────────────────────
    public function index(Request $request)
    {
        $query = Estudiante::query();

        // Filtro por estado
        if ($request->filled('estado')) {
            $query->where('estado', strtoupper($request->estado));
        }

        // Búsqueda general (nombre, apellido, CI o código SIS)
        if ($request->filled('buscar')) {
            $q = $request->buscar;
            $query->where(function ($q2) use ($q) {
                $q2->where('nombre', 'ilike', "%{$q}%")
                    ->orWhere('apellido', 'ilike', "%{$q}%")
                    ->orWhere('ci', 'ilike', "%{$q}%")
                    ->orWhere('codigo_universitario', 'ilike', "%{$q}%");
            });
        }

        // Búsqueda exacta por CI
        if ($request->filled('ci')) {
            $query->where('ci', $request->ci);
        }

        // Búsqueda exacta por código SIS/universitario
        if ($request->filled('codigo_sis')) {
            $query->where('codigo_universitario', $request->codigo_sis);
        }

        return response()->json($query->orderBy('apellido')->paginate(20));
    }

    // ─────────────────────────────────────────────
    // GET /api/estudiantes/buscar
    // Búsqueda exacta por CI o código SIS
    // ─────────────────────────────────────────────
    public function buscarPorIdentificador(Request $request)
    {
        $request->validate([
            'ci'         => 'nullable|string|max:20',
            'codigo_sis' => 'nullable|string|max:50',
        ]);

        if (!$request->filled('ci') && !$request->filled('codigo_sis')) {
            return response()->json([
                'message' => 'Debe proporcionar al menos un identificador: ci o codigo_sis.',
            ], 422);
        }

        $query = Estudiante::query();

        if ($request->filled('ci')) {
            $query->where('ci', $request->ci);
        }

        if ($request->filled('codigo_sis')) {
            $query->orWhere('codigo_universitario', $request->codigo_sis);
        }

        $estudiante = $query->first();

        if (!$estudiante) {
            return response()->json([
                'message' => 'No se encontró ningún estudiante con los identificadores proporcionados.',
                'encontrado' => false,
            ], 404);
        }

        return response()->json([
            'encontrado' => true,
            'estudiante' => $estudiante,
        ]);
    }

    // ─────────────────────────────────────────────
    // POST /api/estudiantes
    // ─────────────────────────────────────────────
    public function store(Request $request)
    {
        $data = $request->validate([
            'ci'                   => 'required|string|max:20|unique:estudiante,ci',
            'nombre'               => 'required|string|max:100',
            'apellido'             => 'required|string|max:100',
            'codigo_universitario' => 'required|string|max:50|unique:estudiante,codigo_universitario',
            'correo'               => 'nullable|email|max:150',
            'estado'               => 'required|in:ACTIVO,INACTIVO',
        ]);

        $estudiante = Estudiante::create($data);

        return response()->json($estudiante, 201);
    }

    // ─────────────────────────────────────────────
    // GET /api/estudiantes/{id}
    // ─────────────────────────────────────────────
    public function show(string $id)
    {
        $estudiante = Estudiante::with(['habilitaciones.examen'])->findOrFail($id);

        return response()->json($estudiante);
    }

    // ─────────────────────────────────────────────
    // PUT/PATCH /api/estudiantes/{id}
    // ─────────────────────────────────────────────
    public function update(Request $request, string $id)
    {
        $estudiante = Estudiante::findOrFail($id);

        $data = $request->validate([
            'ci'                   => "sometimes|string|max:20|unique:estudiante,ci,{$id},id_estudiante",
            'nombre'               => 'sometimes|string|max:100',
            'apellido'             => 'sometimes|string|max:100',
            'codigo_universitario' => "sometimes|string|max:50|unique:estudiante,codigo_universitario,{$id},id_estudiante",
            'correo'               => 'nullable|email|max:150',
            'estado'               => 'sometimes|in:ACTIVO,INACTIVO',
        ]);

        $estudiante->update($data);
        $estudiante->refresh();

        return response()->json([
            'message'    => 'Estudiante actualizado correctamente.',
            'estudiante' => $estudiante,
        ]);
    }

    // ─────────────────────────────────────────────
    // DELETE /api/estudiantes/{id}
    // ─────────────────────────────────────────────
    public function destroy(string $id)
    {
        $estudiante = Estudiante::findOrFail($id);
        $estudiante->delete();

        return response()->json(['message' => 'Estudiante eliminado.']);
    }

    // ─────────────────────────────────────────────
    // POST /api/estudiantes/carga-masiva
    // Carga masiva desde archivo CSV
    // ─────────────────────────────────────────────
    public function cargaMasiva(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file|mimes:csv,txt|max:5120', // máx 5 MB
        ]);

        $archivo = $request->file('archivo');

        try {
            $csv = Reader::createFromPath($archivo->getPathname(), 'r');
            $csv->setHeaderOffset(0); // primera fila = cabeceras
            $csv->setDelimiter(',');
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'No se pudo leer el archivo CSV. Verifique el formato.',
            ], 422);
        }

        // Cabeceras esperadas (insensible a mayúsculas/espacios)
        $columnasRequeridas = ['ci', 'nombre', 'apellido', 'codigo_universitario'];

        $cabeceras = array_map(fn($h) => strtolower(trim($h)), $csv->getHeader());

        foreach ($columnasRequeridas as $col) {
            if (!in_array($col, $cabeceras)) {
                return response()->json([
                    'message' => "El archivo CSV no contiene la columna requerida: \"{$col}\".",
                    'columnas_encontradas' => $cabeceras,
                ], 422);
            }
        }

        $insertados  = [];
        $rechazados  = [];
        $filaNumero  = 1; // empieza en 1 porque fila 0 = cabecera

        // Pre-cargar CI y códigos existentes para chequeo rápido sin N+1
        $cisExistentes      = Estudiante::pluck('ci')->map(fn($v) => strtoupper($v))->flip();
        $codigosExistentes  = Estudiante::pluck('codigo_universitario')->map(fn($v) => strtoupper($v))->flip();

        // CIs y códigos procesados EN ESTE LOTE (para detectar duplicados dentro del CSV)
        $cisEnLote     = [];
        $codigosEnLote = [];

        DB::beginTransaction();

        try {
            foreach ($csv->getRecords() as $fila) {
                $filaNumero++;

                // Normalizar claves (trim + lowercase)
                $fila = array_combine(
                    array_map(fn($k) => strtolower(trim($k)), array_keys($fila)),
                    array_map(fn($v) => trim($v ?? ''), array_values($fila))
                );

                $ci                  = strtoupper($fila['ci'] ?? '');
                $nombre              = ucwords(strtolower($fila['nombre'] ?? ''));
                $apellido            = ucwords(strtolower($fila['apellido'] ?? ''));
                $codigoUniversitario = strtoupper($fila['codigo_universitario'] ?? '');
                $correo              = $fila['correo'] ?? null;
                $estado              = strtoupper($fila['estado'] ?? 'ACTIVO');

                // Validar estado
                if (!in_array($estado, ['ACTIVO', 'INACTIVO'])) {
                    $estado = 'ACTIVO';
                }

                // ---- Validaciones ----
                $erroresFila = [];

                if (empty($ci)) {
                    $erroresFila[] = 'El CI es obligatorio.';
                } elseif (strlen($ci) > 20) {
                    $erroresFila[] = 'El CI supera los 20 caracteres.';
                }

                if (empty($nombre)) {
                    $erroresFila[] = 'El nombre es obligatorio.';
                }

                if (empty($apellido)) {
                    $erroresFila[] = 'El apellido es obligatorio.';
                }

                if (empty($codigoUniversitario)) {
                    $erroresFila[] = 'El código universitario es obligatorio.';
                } elseif (strlen($codigoUniversitario) > 50) {
                    $erroresFila[] = 'El código universitario supera los 50 caracteres.';
                }

                if (!empty($correo) && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                    $erroresFila[] = 'El correo electrónico no es válido.';
                }

                // ---- Detección de duplicados con la BD ----
                if (!empty($ci) && isset($cisExistentes[$ci])) {
                    $erroresFila[] = "Ya existe un estudiante con CI \"{$ci}\" en la base de datos.";
                }

                if (!empty($codigoUniversitario) && isset($codigosExistentes[$codigoUniversitario])) {
                    $erroresFila[] = "Ya existe un estudiante con código \"{$codigoUniversitario}\" en la base de datos.";
                }

                // ---- Detección de duplicados dentro del mismo CSV ----
                if (!empty($ci) && in_array($ci, $cisEnLote)) {
                    $erroresFila[] = "El CI \"{$ci}\" está duplicado dentro del archivo CSV.";
                }

                if (!empty($codigoUniversitario) && in_array($codigoUniversitario, $codigosEnLote)) {
                    $erroresFila[] = "El código \"{$codigoUniversitario}\" está duplicado dentro del archivo CSV.";
                }

                // ---- Resultado por fila ----
                if (!empty($erroresFila)) {
                    $rechazados[] = [
                        'fila'    => $filaNumero,
                        'ci'      => $ci ?: '(vacío)',
                        'nombre'  => trim("{$nombre} {$apellido}") ?: '(vacío)',
                        'motivos' => $erroresFila,
                    ];
                    continue;
                }

                // Registrar en lote para detectar duplicados internos
                $cisEnLote[]     = $ci;
                $codigosEnLote[] = $codigoUniversitario;

                // Insertar estudiante
                $nuevo = Estudiante::create([
                    'ci'                   => $ci,
                    'nombre'               => $nombre,
                    'apellido'             => $apellido,
                    'codigo_universitario' => $codigoUniversitario,
                    'correo'               => $correo ?: null,
                    'estado'               => $estado,
                ]);

                // Actualizar caché local para filas siguientes
                $cisExistentes[$ci]                          = true;
                $codigosExistentes[$codigoUniversitario]     = true;

                $insertados[] = [
                    'fila'                 => $filaNumero,
                    'id_estudiante'        => $nuevo->id_estudiante,
                    'ci'                   => $nuevo->ci,
                    'nombre'               => "{$nuevo->nombre} {$nuevo->apellido}",
                    'codigo_universitario' => $nuevo->codigo_universitario,
                ];
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Ocurrió un error durante la carga. No se guardó ningún registro.',
                'error'   => $e->getMessage(),
            ], 500);
        }

        $totalFilas = count($insertados) + count($rechazados);

        return response()->json([
            'message'    => "Carga masiva completada. {$totalFilas} filas procesadas.",
            'resumen'    => [
                'total_procesadas' => $totalFilas,
                'insertados'       => count($insertados),
                'rechazados'       => count($rechazados),
            ],
            'insertados' => $insertados,
            'rechazados' => $rechazados,
        ], 201);
    }
}
