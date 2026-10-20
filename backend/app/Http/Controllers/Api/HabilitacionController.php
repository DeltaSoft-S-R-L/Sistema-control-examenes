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

        return response()->json($query->paginate(20));
    }

    public function store(StoreHabilitacionRequest $request)
    {
        $data = $request->validated();
        $data['estado'] = strtoupper($data['estado'] ?? 'HABILITADO');

        if ($data['estado'] === 'NO_HABILITADO') {
            $data['motivo_tipo'] = strtoupper($data['motivo_tipo'] ?? 'OTRO');
            $data['inhabilitado_por'] = auth()->id();
            $data['inhabilitado_at'] = now();
        } else {
            $data['motivo_tipo'] = null;
            $data['motivo_detalle'] = null;
            $data['inhabilitado_por'] = null;
            $data['inhabilitado_at'] = null;
        }

        try {
            $habilitacion = Habilitacion::create($data);
        } catch (QueryException $e) {
            if ($e->getCode() === '23505' || str_contains($e->getMessage(), 'unique') || str_contains($e->getMessage(), 'UNIQUE')) {
                return response()->json([
                    'message' => 'El estudiante ya se encuentra asignado a este examen.',
                    'errors'  => ['id_estudiante' => ['El estudiante ya se encuentra asignado a este examen.']],
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
        $motivo_tipo = null;
        $motivo_detalle = null;
        $inhabilitado_por = null;
        $inhabilitado_at = null;

        if ($estado === 'NO_HABILITADO') {
            $motivo_tipo = strtoupper($data['motivo_tipo'] ?? 'OTRO');
            $motivo_detalle = $data['motivo_detalle'] ?? null;
            $inhabilitado_por = auth()->id();
            $inhabilitado_at = now();
        }

        try {
            $habilitaciones = DB::transaction(function () use ($data, $estado, $motivo_tipo, $motivo_detalle, $inhabilitado_por, $inhabilitado_at) {
                $registros = [];

                foreach ($data['estudiantes'] as $idEstudiante) {
                    $registros[] = Habilitacion::create([
                        'id_examen' => $data['id_examen'],
                        'id_estudiante' => $idEstudiante,
                        'estado' => $estado,
                        'motivo_tipo' => $motivo_tipo,
                        'motivo_detalle' => $motivo_detalle,
                        'inhabilitado_por' => $inhabilitado_por,
                        'inhabilitado_at' => $inhabilitado_at,
                    ]);
                }

                return $registros;
            });
        } catch (QueryException $e) {
            if ($e->getCode() === '23505') {
                throw ValidationException::withMessages([
                    'estudiantes' => ['Uno o más estudiantes ya están asignados a este examen.'],
                ]);
            }
            throw $e;
        }

        return response()->json([
            'message' => 'Habilitación masiva realizada correctamente.',
            'total' => count($habilitaciones),
            'habilitaciones' => collect($habilitaciones)->map(fn ($h) => $h->load(['estudiante', 'examen']))->values(),
        ], 201);
    }

    public function show(string $id)
    {
        return response()->json(Habilitacion::with(['estudiante', 'examen', 'reglasIndividuales'])->findOrFail($id));
    }

    public function update(Request $request, string $id)
    {
        $habilitacion = Habilitacion::findOrFail($id);

        // Retrocompatibilidad frontend
        if ($request->has('motivo') && !$request->has('motivo_detalle')) {
            $request->merge(['motivo_detalle' => $request->input('motivo')]);
        }

        $data = $request->validate([
            'estado' => 'sometimes|required|string|in:HABILITADO,NO_HABILITADO,habilitado,no_habilitado',
            'motivo_tipo' => 'nullable|string|in:ACADEMICO,MORA,DOCUMENTACION,OTRO,academico,mora,documentacion,otro',
            'motivo_detalle' => 'nullable|string|max:500',
        ], [
            'estado.in' => 'El estado debe ser HABILITADO o NO_HABILITADO.',
            'motivo_tipo.in' => 'El tipo de motivo no es válido.',
            'motivo_detalle.max' => 'El motivo no puede superar los 500 caracteres.',
        ]);

        if (isset($data['estado'])) {
            $data['estado'] = strtoupper($data['estado']);
        }
        
        $estadoActual = $data['estado'] ?? $habilitacion->estado;

        if ($estadoActual === 'NO_HABILITADO') {
            $data['motivo_tipo'] = strtoupper($data['motivo_tipo'] ?? $habilitacion->motivo_tipo ?? 'OTRO');
            // Solo registrar quién y cuándo si apenas lo estamos inhabilitando
            if ($habilitacion->estado !== 'NO_HABILITADO') {
                $data['inhabilitado_por'] = auth()->id();
                $data['inhabilitado_at'] = now();
            }
        } else {
            // Si el nuevo estado es HABILITADO, limpiamos todo
            $data['motivo_tipo'] = null;
            $data['motivo_detalle'] = null;
            $data['inhabilitado_por'] = null;
            $data['inhabilitado_at'] = null;
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