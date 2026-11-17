<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\AccionAuditoria;
use App\Enums\CodigoErrorVerificacion;
use App\Enums\MotivoIntentoIngreso;
use App\Http\Controllers\Controller;
use App\Http\Resources\IngresoResource;
use App\Models\AsignacionAmbiente;
use App\Models\Habilitacion;
use App\Models\Ingreso;
use App\Models\IntentoIngreso;
use App\Services\AmbienteValidacionService;
use App\Services\AuditService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class IngresoController extends Controller
{
    public function index(Request $request)
    {
        $query = Ingreso::with(['habilitacion.estudiante', 'asignacionAmbiente.ambiente', 'usuario', 'expulsion']);

        if (strtoupper((string) $request->user()?->rol?->nombre) === 'DOCENTE') {
            $usuario = $request->user();
            $query->whereIn('id_ingreso', DB::table('ingreso as i')
                ->join('habilitacion as h', 'h.id_habilitacion', '=', 'i.id_habilitacion')
                ->join('examen as ex', 'ex.id_examen', '=', 'h.id_examen')
                ->join('estudiante as e', 'e.id_estudiante', '=', 'h.id_estudiante')
                ->join('docente_asignatura as da', function ($join) {
                    $join->on('da.id_asignatura', '=', 'ex.id_asignatura')
                        ->on('da.id_carrera', '=', 'e.id_carrera');
                })
                ->where('da.id_usuario', $usuario->id_usuario)
                ->select('i.id_ingreso'));
        }

        if ($request->has('id_examen')) {
            $query->where('id_examen', $request->id_examen);
        }

        return IngresoResource::collection($query->orderBy('fecha_hora', 'desc')->paginate(20));
    }

    /**
     * Registrar el acceso físico de un estudiante vinculando el id_usuario autenticado (ING-01 e ING-02).
     * POST /api/ingresos
     */
    public function store(Request $request, AmbienteValidacionService $ambienteService): JsonResponse
    {
        // 1. Validación de campos requeridos
        $validated = $request->validate([
            'id_examen'              => ['required', 'integer', 'exists:examen,id_examen'],
            'id_habilitacion'        => ['required', 'integer', 'exists:habilitacion,id_habilitacion'],
            'id_asignacion_ambiente' => ['required', 'integer', 'exists:asignacion_ambiente,id_asignacion_ambiente'],
        ], [
            'id_examen.required'              => 'El campo id_examen es obligatorio.',
            'id_examen.exists'                => 'El examen especificado no existe.',
            'id_habilitacion.required'        => 'El campo id_habilitacion es obligatorio.',
            'id_habilitacion.exists'          => 'La habilitación especificada no existe.',
            'id_asignacion_ambiente.required' => 'El campo id_asignacion_ambiente es obligatorio.',
            'id_asignacion_ambiente.exists'   => 'La asignación de ambiente especificada no existe.',
        ]);
        // 2. Extracción segura del operador de puerta autenticado
        $idUsuario = Auth::guard('sanctum')->id()
            ?? $request->user()?->id_usuario
            ?? $request->user()?->id;

        if (!$idUsuario) {
            return response()->json([
                'message' => 'No autenticado.',
            ], 401);
        }

        // 3. Validar consistencia de Habilitación y Pertenencia al Examen
        $habilitacion = Habilitacion::with('estudiante')->find($validated['id_habilitacion']);
        if (!$habilitacion || (int) $habilitacion->id_examen !== (int) $validated['id_examen']) {
            return response()->json([
                'message' => 'La habilitación no corresponde a este examen.',
                'code'    => CodigoErrorVerificacion::NO_ASOCIADO->value,
                'error'   => CodigoErrorVerificacion::NO_ASOCIADO->value,
            ], 422);
        }

        if (strtoupper((string) $request->user()?->rol?->nombre) === 'DOCENTE'
            && !$this->docentePuedeControlar((int) $validated['id_examen'], (int) $habilitacion->id_estudiante, (int) $idUsuario)) {
            return response()->json([
                'message' => 'No puede registrar ingresos de estudiantes fuera de sus materias y carreras asignadas.',
            ], 403);
        }

        // 4. Validar estado de HABILITADO
        if (strtoupper(trim((string) $habilitacion->estado)) !== 'HABILITADO') {
            try {
                IntentoIngreso::create([
                    'id_estudiante'          => $habilitacion->id_estudiante,
                    'id_examen'              => (int) $validated['id_examen'],
                    'id_asignacion_ambiente' => (int) $validated['id_asignacion_ambiente'],
                    'id_usuario'             => (int) $idUsuario,
                    'fecha_hora'             => now(),
                    'motivo'                 => MotivoIntentoIngreso::ESTUDIANTE_NO_HABILITADO->value,
                ]);
            } catch (\Throwable $e) {
                Log::error('Error al persistir intento de estudiante no habilitado: ' . $e->getMessage(), [
                    'id_habilitacion' => $habilitacion->id_habilitacion,
                    'exception'       => $e,
                ]);
            }

            try {
                AuditService::registrar(
                    accion: AccionAuditoria::INTENTO_INGRESO_NO_HABILITADO->value,
                    entidad: 'ingreso',
                    idRegistro: (string) $habilitacion->id_habilitacion,
                    informacion: [
                        'id_estudiante'          => $habilitacion->id_estudiante,
                        'id_examen'              => (int) $validated['id_examen'],
                        'id_asignacion_ambiente' => (int) $validated['id_asignacion_ambiente'],
                        'motivo'                 => MotivoIntentoIngreso::ESTUDIANTE_NO_HABILITADO->value,
                    ],
                    idUsuario: $idUsuario
                );
            } catch (\Throwable $e) {
                Log::error('Error al registrar auditoría de intento no habilitado: ' . $e->getMessage());
            }

            return response()->json([
                'message' => 'El estudiante no se encuentra habilitado para este examen.',
                'code'    => CodigoErrorVerificacion::NO_HABILITADO->value,
                'error'   => CodigoErrorVerificacion::NO_HABILITADO->value,
            ], 403);
        }

        // 5. Validar que la asignación de ambiente pertenezca al examen y auditar discrepancia (origin/dev + ING-04)
        $asignacionAmbiente = AsignacionAmbiente::with('ambiente')->find($validated['id_asignacion_ambiente']);
        if (!$asignacionAmbiente || (int) $asignacionAmbiente->id_examen !== (int) $validated['id_examen']) {
            $asignacionReal = $habilitacion->id_asignacion_ambiente
                ? AsignacionAmbiente::with('ambiente')->find($habilitacion->id_asignacion_ambiente)
                : AsignacionAmbiente::with('ambiente')->where('id_examen', (int) $validated['id_examen'])->first();

            AuditService::registrar(
                accion: AccionAuditoria::DISCREPANCIA_AMBIENTE_INGRESO->value,
                entidad: 'ingreso',
                idRegistro: (string) $habilitacion->id_estudiante,
                informacion: [
                    'id_estudiante'      => $habilitacion->id_estudiante,
                    'id_examen'          => (int) $validated['id_examen'],
                    'id_habilitacion'    => (int) $validated['id_habilitacion'],
                    'ambiente_intentado' => [
                        'id_asignacion_ambiente' => (int) $validated['id_asignacion_ambiente'],
                        'id_ambiente'            => $asignacionAmbiente?->id_ambiente,
                        'nombre'                 => $asignacionAmbiente?->ambiente?->nombre,
                        'codigo'                 => $asignacionAmbiente?->ambiente?->codigo,
                    ],
                    'ambiente_asignado'  => [
                        'id_asignacion_ambiente' => $asignacionReal?->id_asignacion_ambiente,
                        'id_ambiente'            => $asignacionReal?->id_ambiente,
                        'nombre'                 => $asignacionReal?->ambiente?->nombre,
                        'codigo'                 => $asignacionReal?->ambiente?->codigo,
                    ],
                    'id_usuario_control' => (int) $idUsuario,
                ],
                idUsuario: $idUsuario
            );

            return response()->json([
                'message' => 'El ambiente seleccionado no corresponde a este examen.',
                'code'    => CodigoErrorVerificacion::AMBIENTE_NO_CORRESPONDE->value,
                'error'   => CodigoErrorVerificacion::AMBIENTE_NO_CORRESPONDE->value,
            ], 422);
        }

        // 5.1 Validar correspondencia de ambiente asignado vs actual (ING-04 / S4-ING04-01 / S4-ING04-02 / S4-ING04-03)
        $validacionAmbiente = $ambienteService->validarCorrespondencia(
            $habilitacion,
            (int) $validated['id_asignacion_ambiente'],
            (int) $idUsuario
        );

        if (!$validacionAmbiente['valido']) {
            return response()->json([
                'message'           => 'El estudiante está asignado a otra aula.',
                'code'              => CodigoErrorVerificacion::AULA_INCORRECTA->value,
                'error'             => CodigoErrorVerificacion::AULA_INCORRECTA->value,
                'ambiente_asignado' => $validacionAmbiente['ambiente_asignado'],
                'estudiante'        => $habilitacion->estudiante ? [
                    'id'                   => $habilitacion->estudiante->id_estudiante,
                    'id_estudiante'        => $habilitacion->estudiante->id_estudiante,
                    'nombre'               => $habilitacion->estudiante->nombre,
                    'nombres'              => $habilitacion->estudiante->nombre,
                    'apellido'             => $habilitacion->estudiante->apellido,
                    'apellidos'            => $habilitacion->estudiante->apellido,
                    'ci'                   => $habilitacion->estudiante->ci,
                    'codigo_universitario' => $habilitacion->estudiante->codigo_universitario,
                    'codigo_sis'           => $habilitacion->estudiante->codigo_universitario,
                    'url_foto'             => $habilitacion->estudiante->foto_url,
                    'foto_url'             => $habilitacion->estudiante->foto_url,
                ] : null,
            ], 422);
        }

        // 6. Prevenir reingreso duplicado preventivamente (ING-02 / ING-03)
        $ingresoPrevio = Ingreso::with('asignacionAmbiente.ambiente')
            ->where('id_habilitacion', $validated['id_habilitacion'])
            ->first();

        if ($ingresoPrevio) {
            return $this->responderConflictoReingreso(
                $ingresoPrevio,
                $habilitacion,
                (int) $validated['id_examen'],
                (int) $validated['id_asignacion_ambiente'],
                (int) $idUsuario
            );
        }

        // 7. Persistencia transaccional atómica de ingreso + auditoría forzando reloj del servidor now() (ING-02 / ING-03)
        try {
            $ingreso = DB::transaction(function () use ($validated, $habilitacion, $idUsuario) {
                $nuevoIngreso = Ingreso::create([
                    'id_examen'              => (int) $validated['id_examen'],
                    'id_habilitacion'        => (int) $validated['id_habilitacion'],
                    'id_asignacion_ambiente' => (int) $validated['id_asignacion_ambiente'],
                    'id_usuario'             => (int) $idUsuario,
                    'fecha_hora'             => now(),
                ]);

                // 8. Registro atómico de auditoría dentro de la misma transacción
                AuditService::registrarAtomico(
                    accion: AccionAuditoria::REGISTRO_INGRESO_EXAMEN->value,
                    entidad: 'ingreso',
                    idRegistro: (string) $nuevoIngreso->id_ingreso,
                    informacion: [
                        'id_estudiante'          => $habilitacion->id_estudiante,
                        'id_examen'              => (int) $validated['id_examen'],
                        'id_asignacion_ambiente' => (int) $validated['id_asignacion_ambiente'],
                        'id_habilitacion'        => (int) $validated['id_habilitacion'],
                        'fecha_hora'             => $nuevoIngreso->fecha_hora?->toIso8601String() ?? (string) $nuevoIngreso->fecha_hora,
                    ],
                    idUsuario: $idUsuario
                );

                return $nuevoIngreso;
            });
        } catch (QueryException $e) {
            // Manejo de condición de carrera ante colisión en UNIQUE(id_habilitacion)
            $ingresoConcurrente = Ingreso::with('asignacionAmbiente.ambiente')
                ->where('id_habilitacion', $validated['id_habilitacion'])
                ->first();

            if ($ingresoConcurrente) {
                return $this->responderConflictoReingreso(
                    $ingresoConcurrente,
                    $habilitacion,
                    (int) $validated['id_examen'],
                    (int) $validated['id_asignacion_ambiente'],
                    (int) $idUsuario
                );
            }

            throw $e;
        }

        $ingreso->loadMissing(['habilitacion.estudiante', 'asignacionAmbiente.ambiente', 'usuario']);

        return (new IngresoResource($ingreso))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Respuesta estructurada HTTP 409 y registro de intento indebido / auditoría ante intento de doble ingreso (ING-03).
     */
    private function responderConflictoReingreso(
        Ingreso $ingresoPrevio,
        Habilitacion $habilitacion,
        int $idExamen,
        int $idAsignacionAmbiente,
        int $idUsuario
    ): JsonResponse {
        $horaFormateada = $ingresoPrevio->fecha_hora ? $ingresoPrevio->fecha_hora->format('H:i:s') : null;
        $isoTimestamp = $ingresoPrevio->fecha_hora ? $ingresoPrevio->fecha_hora->toIso8601String() : null;
        $nombreAmbiente = $ingresoPrevio->asignacionAmbiente?->ambiente?->nombre ?? 'Aula registrada';

        // 1. Registrar en intento_ingreso
        try {
            IntentoIngreso::create([
                'id_estudiante'          => $habilitacion->id_estudiante,
                'id_examen'              => $idExamen,
                'id_asignacion_ambiente' => $idAsignacionAmbiente,
                'id_usuario'             => $idUsuario,
                'fecha_hora'             => now(),
                'motivo'                 => MotivoIntentoIngreso::REINGRESO_DUPLICADO->value,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error al registrar intento de reingreso duplicado: ' . $e->getMessage(), [
                'id_habilitacion' => $habilitacion->id_habilitacion,
                'exception'       => $e,
            ]);
        }

        // 2. Registrar en auditoría
        try {
            AuditService::registrar(
                accion: AccionAuditoria::INTENTO_REINGRESO_DUPLICADO->value,
                entidad: 'ingreso',
                idRegistro: (string) $ingresoPrevio->id_ingreso,
                informacion: [
                    'id_habilitacion'           => $habilitacion->id_habilitacion,
                    'id_estudiante'             => $habilitacion->id_estudiante,
                    'id_examen'                 => $idExamen,
                    'id_asignacion_ambiente'    => $idAsignacionAmbiente,
                    'primer_ingreso'            => $horaFormateada,
                    'fecha_hora_primer_ingreso' => $isoTimestamp,
                    'ambiente'                  => $nombreAmbiente,
                    'motivo'                    => MotivoIntentoIngreso::REINGRESO_DUPLICADO->value,
                ],
                idUsuario: $idUsuario
            );
        } catch (\Throwable $e) {
            Log::error('Error al registrar auditoría de intento de reingreso: ' . $e->getMessage(), [
                'id_ingreso' => $ingresoPrevio->id_ingreso,
                'exception'  => $e,
            ]);
        }

        return response()->json([
            'message'                   => 'El estudiante ya registró su ingreso a este examen.',
            'code'                      => CodigoErrorVerificacion::REINGRESO_NO_PERMITIDO->value,
            'error'                     => CodigoErrorVerificacion::REINGRESO_NO_PERMITIDO->value,
            'primer_ingreso'            => $horaFormateada,
            'fecha_hora_primer_ingreso' => $isoTimestamp,
            'ambiente'                  => $nombreAmbiente,
        ], 409);
    }

    public function show(string $id)
    {
        $ingreso = Ingreso::with(['habilitacion.estudiante', 'asignacionAmbiente.ambiente', 'usuario', 'expulsion'])->findOrFail($id);
        $usuario = request()->user();
        if (strtoupper((string) $usuario?->rol?->nombre) === 'DOCENTE'
            && !$this->docentePuedeControlar((int) $ingreso->id_examen, (int) $ingreso->habilitacion?->id_estudiante, (int) $usuario->id_usuario)) {
            return response()->json(['message' => 'No tiene permisos para consultar este ingreso.'], 403);
        }

        return response()->json($ingreso);
    }

    private function docentePuedeControlar(int $idExamen, int $idEstudiante, int $idUsuario): bool
    {
        return DB::table('docente_asignatura as da')
            ->join('examen as ex', 'ex.id_asignatura', '=', 'da.id_asignatura')
            ->join('estudiante as e', 'e.id_carrera', '=', 'da.id_carrera')
            ->where('da.id_usuario', $idUsuario)
            ->where('ex.id_examen', $idExamen)
            ->where('e.id_estudiante', $idEstudiante)
            ->exists();
    }

    public function update(Request $request, string $id)
    {
        return response()->json(['message' => 'No permitido.'], 403);
    }

    public function destroy(string $id)
    {
        return response()->json(['message' => 'No permitido.'], 403);
    }
}
