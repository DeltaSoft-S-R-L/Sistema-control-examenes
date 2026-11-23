<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Enums\AccionAuditoria;
use App\Enums\MotivoExpulsion;
use App\Models\Expulsion;
use App\Models\Estudiante;
use App\Models\Incidencia;
use App\Models\Ingreso;
use App\Models\Habilitacion;
use App\Models\AsignacionAmbiente;
use App\Services\AuditService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class IncidenciaController extends Controller
{
    public function registrarCambioAmbiente(Request $request)
    {
        if (!Schema::hasColumn('habilitacion', 'id_asignacion_ambiente')) {
            return response()->json([
                'code' => 'MIGRACION_REQUERIDA',
                'message' => 'La base de datos requiere la migración de asignación de ambiente para habilitaciones.',
            ], 409);
        }

        $this->normalizarIdEstudiante($request);

        $data = $request->validate([
            'id_examen' => ['required', 'integer', 'exists:examen,id_examen'],
            'id_estudiante' => ['required', 'integer', 'exists:estudiante,id_estudiante'],
            'id_asignacion_ambiente' => ['required', 'integer', 'exists:asignacion_ambiente,id_asignacion_ambiente'],
            'descripcion' => ['required', 'string', 'max:2000'],
        ], [
            'id_estudiante.exists' => 'El estudiante no existe en el sistema (ingrese un ID, CI o código universitario válido).',
            'id_estudiante.integer' => 'El identificador del estudiante no es válido.',
            'id_examen.exists' => 'El examen seleccionado no existe.',
        ]);

        if (!$this->docentePuedeGestionar($request, (int) $data['id_examen'], (int) $data['id_estudiante'])) {
            return response()->json(['message' => 'No puede registrar incidencias fuera de sus materias y carreras asignadas.'], 403);
        }

        $asignacion = AsignacionAmbiente::query()
            ->where('id_asignacion_ambiente', $data['id_asignacion_ambiente'])
            ->where('id_examen', $data['id_examen'])
            ->first();
        if (!$asignacion) {
            return response()->json(['message' => 'El ambiente seleccionado no está asignado a este examen.'], 422);
        }

        $incidencia = DB::transaction(function () use ($request, $data, $asignacion) {
            $habilitacion = Habilitacion::query()
                ->where('id_examen', $data['id_examen'])
                ->where('id_estudiante', $data['id_estudiante'])
                ->lockForUpdate()
                ->first();
            if (!$habilitacion) {
                return null;
            }
            if ($habilitacion->ingreso()->exists()) {
                abort(409, 'No se puede cambiar el ambiente después de registrar el ingreso.');
            }

            $anterior = $habilitacion->id_asignacion_ambiente;
            $habilitacion->update(['id_asignacion_ambiente' => $asignacion->id_asignacion_ambiente]);
            return Incidencia::create([
                'id_estudiante' => $data['id_estudiante'],
                'id_examen' => $data['id_examen'],
                'id_usuario' => $request->user()->id_usuario,
                'tipo' => 'CAMBIO_AMBIENTE',
                'descripcion' => $data['descripcion'] . " (Asignación anterior: " . ($anterior ?? 'sin asignar') . "; nueva: {$asignacion->id_asignacion_ambiente}).",
                'fecha_hora' => now(),
            ]);
        });

        if (!$incidencia) {
            return response()->json(['message' => 'El estudiante no está habilitado para este examen.'], 404);
        }

        return response()->json($incidencia->load(['estudiante', 'examen', 'usuario']), 201);
    }

    public function registrarExpulsion(Request $request, ?int $examen = null)
    {
        $this->normalizarIdEstudiante($request);

        $data = $request->validate([
            'id_estudiante' => ['nullable', 'integer', 'required_without:id_ingreso', 'exists:estudiante,id_estudiante'],
            'id_ingreso' => ['nullable', 'integer', 'required_without:id_estudiante', 'exists:ingreso,id_ingreso'],
            'id_examen' => ['nullable', 'integer', 'exists:examen,id_examen'],
            'motivo' => ['required', Rule::enum(MotivoExpulsion::class)],
            'descripcion' => ['nullable', 'string', 'max:1000', Rule::requiredIf(fn () => $request->input('motivo') === MotivoExpulsion::OTRO->value)],
        ], [
            'id_estudiante.exists' => 'El estudiante no existe en el sistema (ingrese un ID, CI o código universitario válido).',
            'id_estudiante.integer' => 'El identificador del estudiante no es válido.',
            'id_examen.exists' => 'El examen seleccionado no existe.',
        ]);
        $idExamen = $examen ?? ($data['id_examen'] ?? null);
        if (!$idExamen) {
            return response()->json(['message' => 'Debe indicar el examen.', 'code' => 'EXAMEN_REQUERIDO'], 422);
        }
        $idUsuario = Auth::guard('sanctum')->id() ?? $request->user()?->id_usuario ?? $request->user()?->id;
        if (!$idUsuario) return response()->json(['message' => 'No autenticado.'], 401);

        try {
            $result = DB::transaction(function () use ($request, $data, $idExamen, $idUsuario) {
                $query = Ingreso::query()->where('id_examen', $idExamen);
                if (!empty($data['id_ingreso'])) $query->where('id_ingreso', $data['id_ingreso']);
                if (!empty($data['id_estudiante'])) $query->whereHas('habilitacion', fn ($q) => $q->where('id_estudiante', $data['id_estudiante'])->where('id_examen', $idExamen));
                $ingreso = $query->lockForUpdate()->first();
                if (!$ingreso) return ['response' => response()->json(['message' => 'El estudiante no tiene ingreso registrado en este examen.', 'code' => 'ESTUDIANTE_SIN_INGRESO'], 422)];
                if ($ingreso->expulsion()->exists()) return ['response' => response()->json(['message' => 'El estudiante ya fue expulsado.', 'code' => 'ESTUDIANTE_YA_EXPULSADO'], 409)];

                $habilitacion = $ingreso->habilitacion;
                if (strtoupper((string) $request->user()?->rol?->nombre) === 'DOCENTE'
                    && !$this->docentePuedeGestionar($request, (int) $idExamen, (int) $habilitacion->id_estudiante)) {
                    return ['response' => response()->json([
                        'message' => 'No puede expulsar estudiantes fuera de sus materias y carreras asignadas.',
                    ], 403)];
                }

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
        $usuario = $request->user();
        if (strtoupper((string) $usuario?->rol?->nombre) === 'DOCENTE') {
            $query->whereIn('id_incidencia', DB::table('incidencia as i')
                ->join('examen as ex', 'ex.id_examen', '=', 'i.id_examen')
                ->join('docente_asignatura as da', 'da.id_asignatura', '=', 'ex.id_asignatura')
                ->leftJoin('estudiante as e', function ($join) {
                    $join->on('e.id_estudiante', '=', 'i.id_estudiante')
                        ->on('e.id_carrera', '=', 'da.id_carrera');
                })
                ->where('da.id_usuario', $usuario->id_usuario)
                ->where(function ($scope) {
                    $scope->whereNull('i.id_estudiante')->orWhereNotNull('e.id_estudiante');
                })
                ->select('i.id_incidencia'));
        }

        if ($request->has('id_examen')) {
            $query->where('id_examen', $request->id_examen);
        }
        if ($request->has('tipo')) {
            $query->where('tipo', $request->tipo);
        }
        if ($request->filled('buscar')) {
            $termino = trim((string) $request->input('buscar'));
            $operador = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where(function ($sub) use ($termino, $operador) {
                $sub->where('descripcion', $operador, "%{$termino}%")
                    ->orWhereHas('estudiante', fn ($est) => $est->where('nombre', $operador, "%{$termino}%")
                        ->orWhere('apellido', $operador, "%{$termino}%")
                        ->orWhere('ci', $operador, "%{$termino}%"));
            });
        }

        return response()->json($query->orderBy('fecha_hora', 'desc')->paginate(20));
    }

    public function store(Request $request)
    {
        $this->normalizarIdEstudiante($request);

        $data = $request->validate([
            'id_estudiante' => ['nullable', 'integer', 'exists:estudiante,id_estudiante'],
            'id_examen'     => ['required', 'integer', 'exists:examen,id_examen'],
            'tipo'          => ['required', 'string', 'max:50'],
            'descripcion'   => ['required', 'string', 'max:2000'],
        ], [
            'id_estudiante.exists' => 'El estudiante no existe en el sistema (ingrese un ID, CI o código universitario válido).',
            'id_estudiante.integer' => 'El identificador del estudiante no es válido.',
            'id_examen.exists' => 'El examen seleccionado no existe.',
        ]);

        if (!$this->docentePuedeGestionar($request, (int) $data['id_examen'], isset($data['id_estudiante']) ? (int) $data['id_estudiante'] : null)) {
            return response()->json(['message' => 'No puede registrar incidencias fuera de sus materias y carreras asignadas.'], 403);
        }

        $data['id_usuario'] = $request->user()->id_usuario;
        $data['fecha_hora'] = now();

        $incidencia = Incidencia::create($data);

        return response()->json($incidencia->load(['estudiante', 'examen', 'usuario']), 201);
    }

    public function show(Request $request, string $id)
    {
        $incidencia = Incidencia::with(['estudiante', 'examen', 'usuario'])->findOrFail($id);
        $usuario = $request->user();
        if (strtoupper((string) $usuario?->rol?->nombre) === 'DOCENTE' && !DB::table('docente_asignatura')
            ->where('id_usuario', $usuario->id_usuario)->where('id_asignatura', $incidencia->examen?->id_asignatura)->exists()) {
            return response()->json(['message' => 'No tiene permisos para consultar esta incidencia.'], 403);
        }
        if (strtoupper((string) $usuario?->rol?->nombre) === 'DOCENTE'
            && !$this->docentePuedeGestionar($request, (int) $incidencia->id_examen, $incidencia->id_estudiante ? (int) $incidencia->id_estudiante : null)) {
            return response()->json(['message' => 'No tiene permisos para consultar esta incidencia.'], 403);
        }

        return response()->json($incidencia);
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

    private function docentePuedeGestionar(Request $request, int $idExamen, ?int $idEstudiante): bool
    {
        $usuario = $request->user();
        if (strtoupper((string) $usuario?->rol?->nombre) !== 'DOCENTE') {
            return true;
        }

        $query = DB::table('docente_asignatura as da')
            ->join('examen as ex', 'ex.id_asignatura', '=', 'da.id_asignatura')
            ->where('da.id_usuario', $usuario->id_usuario)
            ->where('ex.id_examen', $idExamen);

        if ($idEstudiante !== null) {
            $query->join('estudiante as e', function ($join) {
                $join->on('e.id_carrera', '=', 'da.id_carrera');
            })->where('e.id_estudiante', $idEstudiante);
        }

        return $query->exists();
    }

    private function normalizarIdEstudiante(Request $request): void
    {
        if ($request->filled('id_estudiante')) {
            $valor = trim((string) $request->input('id_estudiante'));
            $existePorId = is_numeric($valor) && Estudiante::where('id_estudiante', (int) $valor)->exists();
            if (!$existePorId) {
                $estudiante = Estudiante::where('ci', $valor)
                    ->orWhere('codigo_universitario', $valor)
                    ->first();
                if ($estudiante) {
                    $request->merge(['id_estudiante' => $estudiante->id_estudiante]);
                }
            } else {
                $request->merge(['id_estudiante' => (int) $valor]);
            }
        }
    }
}
