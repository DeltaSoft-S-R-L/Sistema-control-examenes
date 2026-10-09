<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AsignacionAmbiente;
use App\Models\Examen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExamenController extends Controller
{
    // ─────────────────────────────────────────────
    // GET /api/examenes
    // Parámetros opcionales: ?estado=&fecha=&id_asignatura=&buscar=
    // ─────────────────────────────────────────────
    public function index(Request $request)
    {
        $query = Examen::with(['asignatura', 'asignacionesAmbiente.ambiente']);

        if ($request->filled('estado')) {
            $query->where('estado', strtoupper($request->estado));
        }

        if ($request->filled('fecha')) {
            $query->whereDate('fecha', $request->fecha);
        }

        if ($request->filled('id_asignatura')) {
            $query->where('id_asignatura', $request->id_asignatura);
        }

        if ($request->filled('buscar')) {
            $q = $request->buscar;
            $like = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where(function ($sub) use ($q, $like) {
                $sub->where('nombre', $like, "%{$q}%")
                    ->orWhere('descripcion', $like, "%{$q}%");
            });
        }

        return response()->json($query->orderBy('fecha', 'desc')->paginate(20));
    }

    // ─────────────────────────────────────────────
    // POST /api/examenes
    // ─────────────────────────────────────────────
    public function store(Request $request)
    {
        $data = $request->validate([
            'id_asignatura'    => 'required|exists:asignatura,id_asignatura',
            'nombre'           => 'required|string|max:150',
            'fecha'            => 'required|date',
            'hora_inicio'      => 'required|date_format:H:i',
            'duracion_minutos' => 'required|integer|min:1',
            'descripcion'      => 'nullable|string',
            'estado'           => 'required|in:PROGRAMADO,EN_CURSO,FINALIZADO,CANCELADO,programado,en_curso,finalizado,cancelado',
            'id_ambiente'      => 'nullable|exists:ambiente,id_ambiente',
        ]);

        $data['estado'] = strtoupper($data['estado']);
        $idAmbiente = $data['id_ambiente'] ?? null;
        unset($data['id_ambiente']);

        $examen = Examen::create($data);

        if ($idAmbiente) {
            AsignacionAmbiente::create([
                'id_examen'   => $examen->id_examen,
                'id_ambiente' => $idAmbiente,
            ]);
        }

        return response()->json([
            'message' => 'Examen creado correctamente.',
            'examen'  => $examen->load(['asignatura', 'asignacionesAmbiente.ambiente']),
        ], 201);
    }

    // ─────────────────────────────────────────────
    // GET /api/examenes/{id}
    // ─────────────────────────────────────────────
    public function show(string $id)
    {
        $examen = Examen::with([
            'asignatura',
            'asignacionesAmbiente.ambiente',
            'reglas',
            'habilitaciones.estudiante',
        ])->findOrFail($id);

        return response()->json($examen);
    }

    // ─────────────────────────────────────────────
    // PUT/PATCH /api/examenes/{id}
    // ─────────────────────────────────────────────
    public function update(Request $request, string $id)
    {
        $examen = Examen::findOrFail($id);

        $data = $request->validate([
            'id_asignatura'    => 'sometimes|required|exists:asignatura,id_asignatura',
            'nombre'           => 'sometimes|required|string|max:150',
            'fecha'            => 'sometimes|required|date',
            'hora_inicio'      => 'sometimes|required|date_format:H:i',
            'duracion_minutos' => 'sometimes|required|integer|min:1',
            'descripcion'      => 'nullable|string',
            'estado'           => 'sometimes|required|in:PROGRAMADO,EN_CURSO,FINALIZADO,CANCELADO,programado,en_curso,finalizado,cancelado',
            'id_ambiente'      => 'nullable|exists:ambiente,id_ambiente',
        ]);

        if (isset($data['estado'])) {
            $data['estado'] = strtoupper($data['estado']);
        }

        if (array_key_exists('id_ambiente', $data)) {
            $idAmbiente = $data['id_ambiente'];
            unset($data['id_ambiente']);

            AsignacionAmbiente::where('id_examen', $examen->id_examen)->delete();

            if ($idAmbiente) {
                AsignacionAmbiente::create([
                    'id_examen'   => $examen->id_examen,
                    'id_ambiente' => $idAmbiente,
                ]);
            }
        }

        $examen->update($data);
        $examen->refresh();

        return response()->json([
            'message' => 'Examen actualizado correctamente.',
            'examen'  => $examen->load(['asignatura', 'asignacionesAmbiente.ambiente']),
        ]);
    }

    // ─────────────────────────────────────────────
    // DELETE /api/examenes/{id}
    // Solo permite eliminar si está en estado PROGRAMADO o CANCELADO
    // ─────────────────────────────────────────────
    public function destroy(string $id)
    {
        $examen = Examen::findOrFail($id);

        if (!in_array($examen->estado, ['PROGRAMADO', 'CANCELADO'])) {
            return response()->json([
                'message' => "No se puede eliminar un examen en estado \"{$examen->estado}\". Solo se permiten eliminar exámenes en estado PROGRAMADO o CANCELADO.",
            ], 409);
        }

        // Eliminar asignaciones de ambiente asociadas
        AsignacionAmbiente::where('id_examen', $examen->id_examen)->delete();

        $examen->delete();

        return response()->json(['message' => 'Examen eliminado correctamente.']);
    }
}
