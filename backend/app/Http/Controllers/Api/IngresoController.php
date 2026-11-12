<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AsignacionAmbiente;
use App\Models\Habilitacion;
use App\Models\Ingreso;
use App\Services\AuditService;
use Illuminate\Http\Request;

class IngresoController extends Controller
{
    public function index(Request $request)
    {
        $query = Ingreso::with(['habilitacion.estudiante', 'asignacionAmbiente.ambiente', 'usuario']);

        if ($request->has('id_examen')) {
            $query->where('id_examen', $request->id_examen);
        }

        return response()->json($query->orderBy('fecha_hora', 'desc')->paginate(20));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_examen'              => 'required|exists:examen,id_examen',
            'id_habilitacion'        => 'required|exists:habilitacion,id_habilitacion',
            'id_asignacion_ambiente' => 'required|exists:asignacion_ambiente,id_asignacion_ambiente',
        ]);
        $idExamen             = $data['id_examen'];
        $idHabilitacion       = $data['id_habilitacion'];
        $idAsignacionAmbiente = $data['id_asignacion_ambiente'];
        $idUsuarioControl     = $request->user()->id_usuario;


        // ── 1. Verificar discrepancia de ambiente ─────────────────────
        // La asignación enviada debe pertenecer al mismo examen.
        $asignacion = AsignacionAmbiente::where('id_asignacion_ambiente', $idAsignacionAmbiente)
            ->where('id_examen', $idExamen)
            ->first();

        if (!$asignacion) {
            // Obtener datos para el log de auditoría
            $habilitacion      = Habilitacion::with('estudiante')->find($idHabilitacion);
            $asignacionReal    = AsignacionAmbiente::with('ambiente')
                ->where('id_examen', $idExamen)
                ->first();
            $asignacionIntento = AsignacionAmbiente::with('ambiente')->find($idAsignacionAmbiente);

            AuditService::registrar(
                accion: 'DISCREPANCIA_AMBIENTE_INGRESO',
                entidad: 'ingreso',
                idRegistro: $habilitacion?->id_estudiante,
                informacion: [
                    'id_estudiante'       => $habilitacion?->id_estudiante,
                    'id_examen'           => $idExamen,
                    'id_habilitacion'     => $idHabilitacion,
                    'ambiente_intentado'  => [
                        'id_asignacion_ambiente' => $idAsignacionAmbiente,
                        'id_ambiente'            => $asignacionIntento?->id_ambiente,
                        'nombre'                 => $asignacionIntento?->ambiente?->nombre,
                        'codigo'                 => $asignacionIntento?->ambiente?->codigo,
                    ],
                    'ambiente_asignado'   => [
                        'id_asignacion_ambiente' => $asignacionReal?->id_asignacion_ambiente,
                        'id_ambiente'            => $asignacionReal?->id_ambiente,
                        'nombre'                 => $asignacionReal?->ambiente?->nombre,
                        'codigo'                 => $asignacionReal?->ambiente?->codigo,
                    ],
                    'id_usuario_control'  => $idUsuarioControl,
                ],
                idUsuario: $idUsuarioControl
            );

            return response()->json([
                'message' => 'El aula seleccionada no corresponde a este examen.',
                'code'    => 'AMBIENTE_INCORRECTO',
            ], 422);
        }

        // ── 2. Evitar duplicado (ingreso ya existe para esta habilitación) ─
        // No dejamos que la BD lance una excepción 500 por el UNIQUE de id_habilitacion.
        $ingresoPrevio = Ingreso::where('id_habilitacion', $idHabilitacion)->first();

        if ($ingresoPrevio) {
            return response()->json([
                'message'    => 'El estudiante ya tiene un ingreso registrado para este examen.',
                'code'       => 'INGRESO_DUPLICADO',
                'id_ingreso' => $ingresoPrevio->id_ingreso,
            ], 422);
        }

        // ── 3. Registrar el ingreso ───────────────────────────────────
        $ingreso = Ingreso::create([
            'id_examen'              => $idExamen,
            'id_habilitacion'        => $idHabilitacion,
            'id_asignacion_ambiente' => $idAsignacionAmbiente,
            'id_usuario'             => $idUsuarioControl,
            'fecha_hora'             => now(),
        ]);

        return response()->json($ingreso->load(['habilitacion.estudiante', 'usuario']), 201);
    }

    public function show(string $id)
    {
        return response()->json(
            Ingreso::with(['habilitacion.estudiante', 'asignacionAmbiente.ambiente', 'usuario', 'expulsion'])->findOrFail($id)
        );
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
