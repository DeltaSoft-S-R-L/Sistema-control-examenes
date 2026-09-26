<?php

namespace App\Http\Controllers;

use App\Models\Estudiante;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EstudianteController extends Controller
{
    /**
     * Consulta y búsqueda paginada de estudiantes con filtros.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'codigo_universitario' => ['nullable', 'string', 'max:50'],
            'codigo_sis' => ['nullable', 'string', 'max:50'],
            'ci' => ['nullable', 'string', 'max:20'],
            'estado' => ['nullable', 'string', 'in:ACTIVO,INACTIVO,activo,inactivo'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Estudiante::query();

        // 1. Filtro general de búsqueda (nombre, apellido, ci, codigo_universitario)
        if ($request->filled('search')) {
            $search = '%' . trim($request->query('search')) . '%';
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(nombre) LIKE LOWER(?)', [$search])
                  ->orWhereRaw('LOWER(apellido) LIKE LOWER(?)', [$search])
                  ->orWhereRaw('LOWER(codigo_universitario) LIKE LOWER(?)', [$search])
                  ->orWhereRaw('LOWER(ci) LIKE LOWER(?)', [$search]);
            });
        }

        // 2. Filtro específico por código universitario / SIS
        if ($request->filled('codigo_universitario')) {
            $query->where('codigo_universitario', trim($request->query('codigo_universitario')));
        } elseif ($request->filled('codigo_sis')) {
            $query->where('codigo_universitario', trim($request->query('codigo_sis')));
        }

        // 3. Filtro específico por carnet de identidad (CI)
        if ($request->filled('ci')) {
            $query->where('ci', trim($request->query('ci')));
        }

        // 4. Filtro por estado del estudiante (ACTIVO / INACTIVO)
        if ($request->filled('estado')) {
            $query->where('estado', strtoupper(trim($request->query('estado'))));
        }

        // 5. Cargar relaciones si se solicita
        if ($request->query('include_habilitaciones') === 'true') {
            $query->with('habilitaciones');
        }

        // 6. Límite / paginación limpia
        $limit = (int) $request->query('limit', 15);
        $estudiantes = $query->orderBy('id_estudiante', 'asc')->paginate($limit);

        return response()->json($estudiantes, 200);
    }

    /**
     * Consulta de un estudiante por ID numérico o identificador único (Código SIS / CI / Correo).
     *
     * @param string $criterio ID numérico, código universitario o CI
     * @param Request $request
     * @return JsonResponse
     */
    public function show(string $criterio, Request $request): JsonResponse
    {
        $criterio = trim($criterio);

        if ($criterio === '') {
            return response()->json([
                'error' => 'El criterio de búsqueda no puede estar vacío'
            ], 400);
        }

        $query = Estudiante::query();

        if ($request->query('include_habilitaciones') === 'true') {
            $query->with('habilitaciones');
        }

        // 1. Si es numérico entero, intentar buscar prioritariamente por id_estudiante
        if (ctype_digit($criterio)) {
            $estudiante = (clone $query)->where('id_estudiante', $criterio)->first();
            if ($estudiante) {
                return response()->json([
                    'message' => 'Estudiante encontrado exitosamente',
                    'estudiante' => $estudiante,
                ], 200);
            }
        }

        // 2. Si no es numérico o no coincidió con ID, buscar por código universitario, CI o correo
        $estudiante = $query->where('codigo_universitario', $criterio)
            ->orWhere('ci', $criterio)
            ->orWhereRaw('LOWER(correo) = ?', [strtolower($criterio)])
            ->first();

        // 3. Si no existe en ningún campo unívoco
        if (!$estudiante) {
            return response()->json([
                'error' => 'Estudiante no encontrado'
            ], 404);
        }

        return response()->json([
            'message' => 'Estudiante encontrado exitosamente',
            'estudiante' => $estudiante,
        ], 200);
    }
}
