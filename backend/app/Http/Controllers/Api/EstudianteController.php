<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Estudiante;
use Illuminate\Http\Request;

class EstudianteController extends Controller
{
    public function index(Request $request)
    {
        $query = Estudiante::query();

        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
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
        $data = $request->validate([
            'ci'                   => 'required|string|max:20|unique:estudiante,ci',
            'nombre'               => 'required|string|max:100',
            'apellido'             => 'required|string|max:100',
            'codigo_universitario' => 'required|string|max:50|unique:estudiante,codigo_universitario',
            'correo'               => 'nullable|email|max:150',
            'estado'               => ['required', \Illuminate\Validation\Rule::in(['ACTIVO', 'INACTIVO', 'activo', 'inactivo'])],
        ]);

        $data['estado'] = strtoupper($data['estado']);

        $estudiante = Estudiante::create($data);

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

        // Volver al inicio del archivo para leer los encabezados.
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

        // Quitar posibles espacios y BOM del primer encabezado.
        $encabezados = array_map('trim', $encabezados);
        $encabezados[0] = preg_replace(
            '/^\xEF\xBB\xBF/',
            '',
            $encabezados[0]
        );
        // Quitar columnas vacías adicionales al final generadas por Excel.
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
            // Quitar columnas vacías adicionales al final generadas por Excel.
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
            ]);

            if ($validator->fails()) {
                $errores[] = [
                    'fila' => $fila,
                    'mensaje' => $validator->errors()->first(),
                ];

                continue;
            }

            Estudiante::create([
                'ci' => $estudiante['ci'],
                'codigo_universitario' => $estudiante['codigo_universitario'],
                'nombre' => $estudiante['nombre'],
                'apellido' => $estudiante['apellido'],
                'correo' => $estudiante['correo'] ?: null,
                'estado' => $estudiante['estado'],
            ]);

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
        $estudiante = Estudiante::with(['habilitaciones.examen'])->findOrFail($id);

        return response()->json($estudiante);
    }

    public function update(Request $request, string $id)
    {
        $estudiante = Estudiante::findOrFail($id);

        $data = $request->validate([
            'ci'                   => "sometimes|required|string|max:20|unique:estudiante,ci,{$id},id_estudiante",
            'nombre'               => 'sometimes|required|string|max:100',
            'apellido'             => 'sometimes|required|string|max:100',
            'codigo_universitario' => "sometimes|required|string|max:50|unique:estudiante,codigo_universitario,{$id},id_estudiante",
            'correo'               => 'nullable|email|max:150',
            'estado'               => 'sometimes|in:ACTIVO,INACTIVO',
        ]);
        if (isset($data['estado'])) {
            $data['estado'] = strtoupper($data['estado']);
        }
        $estudiante->update($data);

        return response()->json($estudiante);
    }

    public function destroy(string $id)
    {
        $estudiante = Estudiante::findOrFail($id);
        $estudiante->delete();

        return response()->json(['message' => 'Estudiante eliminado.']);
    }
}
