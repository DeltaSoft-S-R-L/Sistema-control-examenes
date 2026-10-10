<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreHabilitacionRequest;
use App\Models\Habilitacion;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

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

        return response()->json($query->paginate(20));
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

    public function show(string $id)
    {
        return response()->json(
            Habilitacion::with(['estudiante', 'examen', 'reglasIndividuales'])->findOrFail($id)
        );
    }

    public function update(Request $request, string $id)
    {
        $habilitacion = Habilitacion::findOrFail($id);

        $data = $request->validate([
            'estado' => 'sometimes|required|string|in:HABILITADO,NO_HABILITADO,habilitado,no_habilitado',
            'motivo' => 'nullable|string|max:500|required_if:estado,NO_HABILITADO,no_habilitado',
        ], [
            'estado.in'          => 'El estado debe ser HABILITADO o NO_HABILITADO.',
            'motivo.required_if' => 'El motivo es obligatorio cuando el estado es NO_HABILITADO.',
            'motivo.max'         => 'El motivo no puede superar los 500 caracteres.',
        ]);

        if (isset($data['estado'])) {
            $data['estado'] = strtoupper($data['estado']);
        }

        $habilitacion->update($data);

        return response()->json($habilitacion);
    }

    public function destroy(string $id)
    {
        Habilitacion::findOrFail($id)->delete();

        return response()->json(['message' => 'Habilitación eliminada.']);
    }
}
