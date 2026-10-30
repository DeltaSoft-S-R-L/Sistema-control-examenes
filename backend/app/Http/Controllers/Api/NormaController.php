<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Examen;
use App\Models\Habilitacion;
use App\Models\ReglaExamen;
use App\Models\ReglaIndividual;
use App\Services\AuditoriaService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class NormaController extends Controller
{
    /**
     * Listar todas las normas (generales y particulares) de un examen.
     * GET /api/examenes/{id}/normas
     */
    public function index(string $id)
    {
        $examen = Examen::findOrFail($id);

        // Normas generales del examen
        $reglasGenerales = ReglaExamen::where('id_examen', $examen->id_examen)
            ->get()
            ->map(function ($r) {
                return [
                    'id_norma'        => $r->id_regla_examen,
                    'tipo'            => 'GENERAL',
                    'descripcion'     => $r->descripcion,
                    'estado'          => $r->estado,
                    'id_habilitacion' => null,
                    'estudiante'      => null,
                ];
            });

        // Normas particulares asociadas a habilitaciones del examen
        $reglasParticulares = ReglaIndividual::whereHas('habilitacion', function ($q) use ($examen) {
            $q->where('id_examen', $examen->id_examen);
        })
        ->with('habilitacion.estudiante')
        ->get()
        ->map(function ($r) {
            $estudiante = $r->habilitacion?->estudiante;
            return [
                'id_norma'        => $r->id_regla_individual,
                'tipo'            => 'PARTICULAR',
                'descripcion'     => $r->descripcion,
                'estado'          => $r->estado,
                'id_habilitacion' => $r->id_habilitacion,
                'estudiante'      => $estudiante ? [
                    'id_estudiante'        => $estudiante->id_estudiante,
                    'nombre'               => $estudiante->nombre,
                    'apellido'             => $estudiante->apellido,
                    'nombre_completo'      => "{$estudiante->nombre} {$estudiante->apellido}",
                    'ci'                   => $estudiante->ci,
                    'codigo_universitario' => $estudiante->codigo_universitario,
                ] : null,
            ];
        });

        $todas = $reglasGenerales->concat($reglasParticulares)->values();

        return response()->json($todas);
    }

    /**
     * Crear una nueva norma (General o Particular) para un examen.
     * POST /api/examenes/{id}/normas
     */
    public function store(Request $request, string $id)
    {
        $examen = Examen::findOrFail($id);

        $request->validate([
            'tipo'            => 'required|string|in:GENERAL,PARTICULAR,general,particular',
            'descripcion'     => 'required|string|max:1000',
            'id_habilitacion' => 'nullable|integer',
            'id_estudiante'   => 'nullable|integer',
        ], [
            'tipo.required'        => 'El tipo de norma es obligatorio.',
            'tipo.in'              => 'El tipo de norma debe ser General o Particular.',
            'descripcion.required' => 'La descripción de la norma es obligatoria.',
            'descripcion.max'      => 'La descripción no puede superar los 1000 caracteres.',
        ]);

        $tipo = strtoupper($request->tipo);
        $descripcion = trim($request->descripcion);

        if ($descripcion === '') {
            throw ValidationException::withMessages([
                'descripcion' => ['La descripción de la norma no puede estar vacía.'],
            ]);
        }

        if ($tipo === 'GENERAL') {
            $regla = ReglaExamen::create([
                'id_examen'   => $examen->id_examen,
                'descripcion' => $descripcion,
                'estado'      => 'ACTIVA',
            ]);

            AuditoriaService::registrar(
                $request->user()?->id_usuario,
                'CREACION_NORMA',
                'EXAMEN',
                $examen->id_examen,
                'EXITO',
                "Norma general agregada al examen ID {$examen->id_examen}: \"{$descripcion}\""
            );

            return response()->json([
                'message' => 'Norma general registrada correctamente.',
                'norma'   => [
                    'id_norma'        => $regla->id_regla_examen,
                    'tipo'            => 'GENERAL',
                    'descripcion'     => $regla->descripcion,
                    'estado'          => $regla->estado,
                    'id_habilitacion' => null,
                    'estudiante'      => null,
                ],
            ], 201);
        }

        // Tipo PARTICULAR
        $idHabilitacion = $request->id_habilitacion;
        $idEstudiante = $request->id_estudiante;

        $habilitacion = null;

        if ($idHabilitacion) {
            $habilitacion = Habilitacion::where('id_habilitacion', $idHabilitacion)
                ->where('id_examen', $examen->id_examen)
                ->with('estudiante')
                ->first();
        }

        if (!$habilitacion && $idEstudiante) {
            $habilitacion = Habilitacion::where('id_estudiante', $idEstudiante)
                ->where('id_examen', $examen->id_examen)
                ->with('estudiante')
                ->first();
        }

        if (!$habilitacion) {
            throw ValidationException::withMessages([
                'id_estudiante' => ['Debe seleccionar un estudiante con habilitación válida para este examen.'],
            ]);
        }

        $regla = ReglaIndividual::create([
            'id_habilitacion' => $habilitacion->id_habilitacion,
            'descripcion'     => $descripcion,
            'estado'          => 'ACTIVA',
        ]);

        $est = $habilitacion->estudiante;

        AuditoriaService::registrar(
            $request->user()?->id_usuario,
            'CREACION_NORMA_PARTICULAR',
            'HABILITACION',
            $habilitacion->id_habilitacion,
            'EXITO',
            "Norma particular para estudiante {$est?->nombre} {$est?->apellido} (CI: {$est?->ci}) en examen ID {$examen->id_examen}: \"{$descripcion}\""
        );

        return response()->json([
            'message' => 'Norma particular registrada correctamente.',
            'norma'   => [
                'id_norma'        => $regla->id_regla_individual,
                'tipo'            => 'PARTICULAR',
                'descripcion'     => $regla->descripcion,
                'estado'          => $regla->estado,
                'id_habilitacion' => $habilitacion->id_habilitacion,
                'estudiante'      => $est ? [
                    'id_estudiante'        => $est->id_estudiante,
                    'nombre'               => $est->nombre,
                    'apellido'             => $est->apellido,
                    'nombre_completo'      => "{$est->nombre} {$est->apellido}",
                    'ci'                   => $est->ci,
                    'codigo_universitario' => $est->codigo_universitario,
                ] : null,
            ],
        ], 201);
    }

    /**
     * Eliminar una norma existente (General o Particular).
     * DELETE /api/examenes/{id}/normas/{id_norma}
     */
    public function destroy(Request $request, string $id, string $idNorma)
    {
        $examen = Examen::findOrFail($id);
        $tipo = strtoupper($request->query('tipo') ?? $request->input('tipo') ?? '');

        $eliminado = false;
        $descripcionEliminada = '';

        if ($tipo === 'GENERAL' || empty($tipo)) {
            $reglaG = ReglaExamen::where('id_regla_examen', $idNorma)
                ->where('id_examen', $examen->id_examen)
                ->first();

            if ($reglaG) {
                $descripcionEliminada = $reglaG->descripcion;
                $reglaG->delete();
                $eliminado = true;
            }
        }

        if (!$eliminado && ($tipo === 'PARTICULAR' || empty($tipo))) {
            $reglaP = ReglaIndividual::where('id_regla_individual', $idNorma)
                ->whereHas('habilitacion', function ($q) use ($examen) {
                    $q->where('id_examen', $examen->id_examen);
                })
                ->first();

            if ($reglaP) {
                $descripcionEliminada = $reglaP->descripcion;
                $reglaP->delete();
                $eliminado = true;
            }
        }

        if (!$eliminado) {
            return response()->json([
                'message' => 'Norma no encontrada en este examen.',
            ], 404);
        }

        AuditoriaService::registrar(
            $request->user()?->id_usuario,
            'ELIMINACION_NORMA',
            'EXAMEN',
            $examen->id_examen,
            'EXITO',
            "Norma eliminada del examen ID {$examen->id_examen}: \"{$descripcionEliminada}\""
        );

        return response()->json([
            'message' => 'Norma eliminada correctamente.',
        ]);
    }
}
