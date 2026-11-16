<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Enums\AccionAuditoria;
use App\Enums\MotivoExpulsion;
use App\Models\Expulsion;
use App\Models\Incidencia;
use App\Models\Ingreso;
use App\Services\AuditService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class IncidenciaController extends Controller
{
    public function registrarExpulsion(Request $request, ?int $examen = null)
    {
        $data = $request->validate([
            'id_estudiante' => ['nullable', 'integer', 'required_without:id_ingreso', 'exists:estudiante,id_estudiante'],
            'id_ingreso' => ['nullable', 'integer', 'required_without:id_estudiante', 'exists:ingreso,id_ingreso'],
            'id_examen' => ['nullable', 'integer', 'exists:examen,id_examen'],
            'motivo' => ['required', Rule::enum(MotivoExpulsion::class)],
            'descripcion' => ['nullable', 'string', 'max:1000', Rule::requiredIf(fn () => $request->input('motivo') === MotivoExpulsion::OTRO->value)],
        ]);
        $idExamen = $examen ?? ($data['id_examen'] ?? null);
        if (!$idExamen) {
            return response()->json(['message' => 'Debe indicar el examen.', 'code' => 'EXAMEN_REQUERIDO'], 422);
        }
        $idUsuario = Auth::guard('sanctum')->id() ?? $request->user()?->id_usuario ?? $request->user()?->id;
        if (!$idUsuario) return response()->json(['message' => 'No autenticado.'], 401);

        try {
            $result = DB::transaction(function () use ($data, $idExamen, $idUsuario) {
                $query = Ingreso::query()->where('id_examen', $idExamen);
                if (!empty($data['id_ingreso'])) $query->where('id_ingreso', $data['id_ingreso']);
                if (!empty($data['id_estudiante'])) $query->whereHas('habilitacion', fn ($q) => $q->where('id_estudiante', $data['id_estudiante'])->where('id_examen', $idExamen));
                $ingreso = $query->lockForUpdate()->first();
                if (!$ingreso) return ['response' => response()->json(['message' => 'El estudiante no tiene ingreso registrado en este examen.', 'code' => 'ESTUDIANTE_SIN_INGRESO'], 422)];
                if ($ingreso->expulsion()->exists()) return ['response' => response()->json(['message' => 'El estudiante ya fue expulsado.', 'code' => 'ESTUDIANTE_YA_EXPULSADO'], 409)];

                $habilitacion = $ingreso->habilitacion;
                $motivo = MotivoExpulsion::from($data['motivo']);
                $now = now();
                $expulsion = Expulsion::create(['id_ingreso' => $ingreso->id_ingreso, 'id_usuario' => $idUsuario, 'motivo' => $motivo->value, 'fecha_hora' => $now]);
                Incidencia::create([
                    'id_estudiante' => $habilitacion->id_estudiante,
                    'id_examen' => $idExamen,
                    'id_usuario' => $idUsuario,
                    'tipo' => $motivo->value,
                    'descripcion' => $data['descripcion'] ?? $motivo->etiqueta(),
                    'fecha_hora' => $now,
                ]);
                $habilitacion->update(['estado' => 'EXPULSADO']);
                AuditService::registrarAtomico(
                    AccionAuditoria::REGISTRO_EXPULSION_ESTUDIANTE,
                    'expulsion',
                    $expulsion->id_expulsion,
                    ['id_estudiante' => $habilitacion->id_estudiante, 'id_examen' => $idExamen, 'id_ingreso' => $ingreso->id_ingreso, 'motivo' => $motivo->value],
                    $idUsuario
                );
                return ['expulsion' => $expulsion, 'ingreso' => $ingreso];
            });
            if (isset($result['response'])) return $result['response'];
            $motivo = MotivoExpulsion::from($data['motivo']);
            return response()->json([
                'id_expulsion' => $result['expulsion']->id_expulsion,
                'id_ingreso' => $result['ingreso']->id_ingreso,
                'id_examen' => (int) $idExamen,
                'motivo' => $result['expulsion']->motivo,
                'descripcion' => $data['descripcion'] ?? $motivo->etiqueta(),
                'fecha_hora' => $result['expulsion']->fecha_hora->toIso8601String(),
            ], 201);
        } catch (QueryException $e) {
            $mensajeSql = strtolower($e->getMessage());
            $esDuplicadoExpulsion = str_contains($mensajeSql, 'expulsion_id_ingreso')
                || str_contains($mensajeSql, 'unique constraint failed: expulsion.id_ingreso');
            if ($esDuplicadoExpulsion && in_array((string) $e->getCode(), ['23000', '23505', '19'], true)) {
                return response()->json(['message' => 'El estudiante ya fue expulsado.', 'code' => 'ESTUDIANTE_YA_EXPULSADO'], 409);
            }
            throw $e;
        }
    }

    public function index(Request $request)
    {
        $query = Incidencia::with(['estudiante', 'examen', 'usuario']);

        if ($request->has('id_examen')) {
            $query->where('id_examen', $request->id_examen);
        }
        if ($request->has('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        return response()->json($query->orderBy('fecha_hora', 'desc')->paginate(20));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_estudiante' => 'nullable|exists:estudiante,id_estudiante',
            'id_examen'     => 'required|exists:examen,id_examen',
            'tipo'          => 'required|string|max:50',
            'descripcion'   => 'required|string',
        ]);

        $data['id_usuario'] = $request->user()->id_usuario;
        $data['fecha_hora'] = now();

        $incidencia = Incidencia::create($data);

        return response()->json($incidencia->load(['estudiante', 'examen', 'usuario']), 201);
    }

    public function show(string $id)
    {
        return response()->json(Incidencia::with(['estudiante', 'examen', 'usuario'])->findOrFail($id));
    }

    public function update(Request $request, string $id)
    {
        $incidencia = Incidencia::findOrFail($id);

        $data = $request->validate([
            'tipo'        => 'sometimes|required|string|max:50',
            'descripcion' => 'sometimes|required|string',
        ]);

        $incidencia->update($data);

        return response()->json($incidencia);
    }

    public function destroy(string $id)
    {
        Incidencia::findOrFail($id)->delete();

        return response()->json(['message' => 'Incidencia eliminada.']);
    }
}
