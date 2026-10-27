<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNormaExamenRequest;
use App\Http\Resources\NormaExamenResource;
use App\Models\Examen;
use App\Models\NormaExamen;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class NormaExamenController extends Controller
{
    public function index(Request $request, string|int $examen): AnonymousResourceCollection|JsonResponse
    {
        $examenModel = Examen::find($examen);

        if (! $examenModel) {
            return response()->json([
                'message' => 'Examen no encontrado.',
            ], 404);
        }

        $query = NormaExamen::with(['estudiante', 'creador'])
            ->where('id_examen', $examenModel->id_examen);

        if ($request->filled('tipo')) {
            $query->where('tipo', strtoupper(trim((string) $request->tipo)));
        }

        $estudianteId = $request->input('estudiante_id', $request->input('id_estudiante'));
        if ($estudianteId !== null && $estudianteId !== '') {
            $query->where('id_estudiante', $estudianteId);
        }

        $normas = $query->orderBy('id_norma_examen', 'asc')->get();

        return NormaExamenResource::collection($normas);
    }

    public function store(StoreNormaExamenRequest $request, string|int $examen): JsonResponse
    {
        $examenModel = Examen::find($examen);

        if (! $examenModel) {
            return response()->json([
                'message' => 'Examen no encontrado.',
            ], 404);
        }

        $data = $request->validated();
        $tipo = strtoupper(trim((string) $data['tipo']));
        $idEstudiante = $tipo === 'PARTICULAR'
            ? ($data['estudiante_id'] ?? $data['id_estudiante'] ?? null)
            : null;

        $norma = NormaExamen::create([
            'id_examen' => $examenModel->id_examen,
            'id_estudiante' => $idEstudiante,
            'tipo' => $tipo,
            'descripcion' => $data['descripcion'],
            'created_by' => $request->user()->id_usuario,
        ]);

        $norma->loadMissing(['estudiante', 'creador']);

        return (new NormaExamenResource($norma))
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(Request $request, string|int $examen, string|int $norma): Response|JsonResponse
    {
        $examenModel = Examen::find($examen);

        if (! $examenModel) {
            return response()->json([
                'message' => 'Examen no encontrado.',
            ], 404);
        }

        $normaModel = NormaExamen::find($norma);

        if (! $normaModel) {
            return response()->json([
                'message' => 'Norma no encontrada.',
            ], 404);
        }

        if ((int) $normaModel->id_examen !== (int) $examenModel->id_examen) {
            return response()->json([
                'message' => 'La norma especificada no pertenece a este examen.',
            ], 404);
        }

        $normaModel->delete();

        return response()->noContent();
    }
}
