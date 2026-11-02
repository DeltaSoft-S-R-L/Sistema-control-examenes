<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNormaExamenRequest;
use App\Http\Resources\NormaExamenResource;
use App\Models\Estudiante;
use App\Models\Examen;
use App\Models\Habilitacion;
use App\Models\NormaExamen;
use App\Models\ReglaExamen;
use App\Models\ReglaIndividual;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NormaController extends Controller
{
    public function index(Request $request, string|int $id): JsonResponse
    {
        $examen = Examen::find($id);

        if (! $examen) {
            return response()->json([
                'message' => 'Examen no encontrado.',
            ], 404);
        }

        $this->sincronizarReglasLegadas($examen);

        $query = NormaExamen::with(['estudiante', 'creador'])
            ->where('id_examen', $examen->id_examen);

        if ($request->filled('tipo')) {
            $query->where('tipo', strtoupper(trim((string) $request->tipo)));
        }

        $estudianteId = $request->input('estudiante_id', $request->input('id_estudiante'));
        if ($estudianteId !== null && $estudianteId !== '') {
            $query->where('id_estudiante', $estudianteId);
        }

        $normas = $query->orderBy('id_norma_examen', 'asc')->get();

        $data = NormaExamenResource::collection($normas)->resolve($request);

        return response()->json($data);
    }

    public function store(StoreNormaExamenRequest $request, string|int $id): JsonResponse
    {
        $examen = Examen::find($id);

        if (! $examen) {
            return response()->json([
                'message' => 'Examen no encontrado.',
            ], 404);
        }

        $data = $request->validated();
        $tipo = strtoupper(trim((string) $data['tipo']));
        $descripcion = trim((string) $data['descripcion']);
        $idEstudiante = $tipo === 'PARTICULAR'
            ? ($data['estudiante_id'] ?? $data['id_estudiante'] ?? null)
            : null;

        $userId = $request->user()?->id_usuario ?? $request->user()?->id;

        $norma = NormaExamen::create([
            'id_examen' => $examen->id_examen,
            'id_estudiante' => $idEstudiante,
            'tipo' => $tipo,
            'descripcion' => $descripcion,
            'created_by' => $userId,
        ]);

        $idHabilitacion = null;
        if ($tipo === 'GENERAL') {
            ReglaExamen::firstOrCreate([
                'id_examen' => $examen->id_examen,
                'descripcion' => $descripcion,
            ], [
                'estado' => 'ACTIVA',
            ]);
        } else {
            $hab = Habilitacion::where('id_examen', $examen->id_examen)
                ->where('id_estudiante', $idEstudiante)
                ->first();
            if ($hab) {
                $idHabilitacion = $hab->id_habilitacion;
                ReglaIndividual::firstOrCreate([
                    'id_habilitacion' => $hab->id_habilitacion,
                    'descripcion' => $descripcion,
                ], [
                    'estado' => 'ACTIVA',
                ]);
            }
        }

        $norma->loadMissing(['estudiante', 'creador']);
        $resourceData = (new NormaExamenResource($norma))->resolve($request);

        $normaPayload = array_merge($resourceData, [
            'id_norma' => $norma->id_norma_examen,
            'estado' => 'ACTIVA',
            'id_habilitacion' => $idHabilitacion,
        ]);

        $message = $tipo === 'GENERAL'
            ? 'Norma general registrada correctamente.'
            : 'Norma particular registrada correctamente.';

        $responsePayload = array_merge($resourceData, [
            'message' => $message,
            'norma' => $normaPayload,
        ]);

        return response()->json($responsePayload, 201);
    }

    public function destroy(Request $request, string|int $id, string|int $id_norma): JsonResponse|\Illuminate\Http\Response
    {
        $examen = Examen::find($id);

        if (! $examen) {
            return response()->json([
                'message' => 'Examen no encontrado.',
            ], 404);
        }

        $tipoFiltro = strtoupper(trim((string) ($request->query('tipo') ?? $request->input('tipo') ?? '')));

        $norma = NormaExamen::find($id_norma);

        if ($norma) {
            if ((int) $norma->id_examen !== (int) $examen->id_examen) {
                return response()->json([
                    'message' => 'La norma especificada no pertenece a este examen.',
                ], 404);
            }

            if ($tipoFiltro && $norma->tipo !== $tipoFiltro) {
                return response()->json([
                    'message' => 'Norma no encontrada en este examen.',
                ], 404);
            }

            if ($norma->tipo === 'GENERAL') {
                ReglaExamen::where('id_examen', $examen->id_examen)
                    ->where('descripcion', $norma->descripcion)
                    ->delete();
            } else {
                ReglaIndividual::whereHas('habilitacion', fn ($q) => $q->where('id_examen', $examen->id_examen))
                    ->where('descripcion', $norma->descripcion)
                    ->delete();
            }

            $norma->delete();

            if ($tipoFiltro !== '') {
                return response()->json([
                    'message' => 'Norma eliminada correctamente.',
                ], 200);
            }

            return response()->noContent();
        }

        $eliminado = false;

        if ($tipoFiltro === 'GENERAL' || empty($tipoFiltro)) {
            $reglaG = ReglaExamen::where('id_regla_examen', $id_norma)
                ->where('id_examen', $examen->id_examen)
                ->first();

            if ($reglaG) {
                NormaExamen::where('id_examen', $examen->id_examen)
                    ->where('descripcion', $reglaG->descripcion)
                    ->delete();
                $reglaG->delete();
                $eliminado = true;
            }
        }

        if (! $eliminado && ($tipoFiltro === 'PARTICULAR' || empty($tipoFiltro))) {
            $reglaP = ReglaIndividual::where('id_regla_individual', $id_norma)
                ->whereHas('habilitacion', fn ($q) => $q->where('id_examen', $examen->id_examen))
                ->first();

            if ($reglaP) {
                NormaExamen::where('id_examen', $examen->id_examen)
                    ->where('descripcion', $reglaP->descripcion)
                    ->delete();
                $reglaP->delete();
                $eliminado = true;
            }
        }

        if (! $eliminado) {
            return response()->json([
                'message' => 'Norma no encontrada.',
            ], 404);
        }

        return response()->json([
            'message' => 'Norma eliminada correctamente.',
        ], 200);
    }

    private function sincronizarReglasLegadas(Examen $examen): void
    {
        $reglasGenerales = ReglaExamen::where('id_examen', $examen->id_examen)->get();
        foreach ($reglasGenerales as $rg) {
            NormaExamen::firstOrCreate([
                'id_examen' => $examen->id_examen,
                'id_estudiante' => null,
                'tipo' => 'GENERAL',
                'descripcion' => $rg->descripcion,
            ], [
                'created_by' => 1,
            ]);
        }

        $reglasParticulares = ReglaIndividual::whereHas('habilitacion', function ($q) use ($examen) {
            $q->where('id_examen', $examen->id_examen);
        })->with('habilitacion')->get();

        foreach ($reglasParticulares as $rp) {
            $idEstudiante = $rp->habilitacion?->id_estudiante;
            if ($idEstudiante) {
                NormaExamen::firstOrCreate([
                    'id_examen' => $examen->id_examen,
                    'id_estudiante' => $idEstudiante,
                    'tipo' => 'PARTICULAR',
                    'descripcion' => $rp->descripcion,
                ], [
                    'created_by' => 1,
                ]);
            }
        }
    }
}
