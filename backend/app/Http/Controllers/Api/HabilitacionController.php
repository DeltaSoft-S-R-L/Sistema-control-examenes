<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreHabilitacionRequest;
use App\Models\Habilitacion;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use App\Http\Requests\StoreHabilitacionMasivaRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HabilitacionController extends Controller
{
    public function index(Request $request)
    {
        $query = Habilitacion::with(['estudiante', 'examen']);

        if ($request->has('id_examen')) {
            $query->where('id_examen', $request->id_examen);
        }
        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        $perPage = min(max($request->integer('per_page', 20), 1), 1000);

        return response()->json($query->paginate($perPage));
    }

    public function store(StoreHabilitacionRequest $request)
    {
        $data = $request->validated();
        $data['estado'] = strtoupper($data['estado'] ?? 'HABILITADO');

        try {
            $habilitacion = Habilitacion::create($data);
        } catch (QueryException $e) {
            // Manejo de condición de carrera si dos peticiones concurrentes pasan la validación simultáneamente
            if ($e->getCode() === '23505' || str_contains($e->getMessage(), 'unique') || str_contains($e->getMessage(), 'UNIQUE')) {
                return response()->json([
                    'message' => 'El estudiante ya se encuentra asignado a este examen.',
                    'errors'  => [
                        'id_estudiante' => ['El estudiante ya se encuentra asignado a este examen.'],
                    ],
                ], 422);
            }

            throw $e;
        }

        return response()->json($habilitacion->load(['estudiante', 'examen']), 201);
    }

public function storeMasiva(StoreHabilitacionMasivaRequest $request)
{
    $data = $request->validated();

    $estado = strtoupper($data['estado'] ?? 'HABILITADO');
    $motivo = $data['motivo'] ?? null;

    try {
        $habilitaciones = DB::transaction(function () use ($data, $estado, $motivo) {
            $registros = [];

            foreach ($data['estudiantes'] as $idEstudiante) {
                $registros[] = Habilitacion::create([
                    'id_examen' => $data['id_examen'],
                    'id_estudiante' => $idEstudiante,
                    'estado' => $estado,
                    'motivo' => $motivo,
                ]);
            }

            return $registros;
        });
    } catch (QueryException $e) {
        if ($e->getCode() === '23505') {
            throw ValidationException::withMessages([
                'estudiantes' => [
                    'Uno o más estudiantes ya están asignados a este examen.',
                ],
            ]);
        }

        throw $e;
    }

    return response()->json([
        'message' => 'Habilitación masiva realizada correctamente.',
        'total' => count($habilitaciones),
        'habilitaciones' => collect($habilitaciones)
            ->map(fn ($habilitacion) => $habilitacion->load(['estudiante', 'examen']))
            ->values(),
    ], 201);
}

    public function show(string $id)
    {
        return response()->json(
            Habilitacion::with(['estudiante', 'examen', 'reglasIndividuales'])->findOrFail($id)
        );
    }

    public function update(Request $request, string $id)
    {
        $habilitacion = Habilitacion::findOrFail($id);
        $usuario = $request->user();
        $usuario->loadMissing('rol');
        $rol = strtoupper((string) $usuario->rol?->nombre);

        $data = $request->validate([
            'estado' => 'sometimes|required|string|in:HABILITADO,NO_HABILITADO,habilitado,no_habilitado',
            'motivo' => 'nullable|string|max:500|required_if:estado,NO_HABILITADO,no_habilitado',
        ], [
            'estado.in'          => 'El estado debe ser HABILITADO o NO_HABILITADO.',
            'motivo.required_if' => 'El motivo es obligatorio cuando el estado es NO_HABILITADO.',
            'motivo.max'         => 'El motivo no puede superar los 500 caracteres.',
        ]);

        if ($rol === 'DOCENTE') {
            if (strtoupper($data['estado'] ?? '') !== 'NO_HABILITADO') {
                return response()->json([
                    'message' => 'Los docentes solo pueden revocar habilitaciones.',
                ], 403);
            }

            $puedeRevocar = DB::table('habilitacion as h')
                ->join('estudiante as e', 'e.id_estudiante', '=', 'h.id_estudiante')
                ->join('examen as ex', 'ex.id_examen', '=', 'h.id_examen')
                ->join('docente_asignatura as da', function ($join) {
                    $join->on('da.id_carrera', '=', 'e.id_carrera')
                        ->on('da.id_asignatura', '=', 'ex.id_asignatura');
                })
                ->where('da.id_usuario', $usuario->id_usuario)
                ->where('h.id_habilitacion', $habilitacion->id_habilitacion)
                ->exists();

            if (!$puedeRevocar) {
                return response()->json([
                    'message' => 'No puede revocar habilitaciones fuera de sus materias asignadas.',
                ], 403);
            }
        } elseif ($rol !== 'ADMINISTRADOR' && !$usuario->tienePermiso('GESTIONAR_HABILITACIONES')) {
            return response()->json([
                'message' => 'No tiene permisos para realizar esta acción.',
            ], 403);
        }

        if (isset($data['estado'])) {
            $data['estado'] = strtoupper($data['estado']);
        }

        $habilitacion = DB::transaction(function () use ($habilitacion, $data) {
            $habilitacion->update($data);

            return $habilitacion->fresh();
        });

        return response()->json($habilitacion);
    }

    public function destroy(string $id)
    {
        Habilitacion::findOrFail($id)->delete();

        return response()->json(['message' => 'Habilitación eliminada.']);
    }
}
