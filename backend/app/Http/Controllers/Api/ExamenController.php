<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Examen;
use Illuminate\Http\Request;

class ExamenController extends Controller
{
    // ─────────────────────────────────────────────
    // GET /api/examenes
    // Parámetros opcionales: ?estado=&fecha=&id_asignatura=&buscar=
    // ─────────────────────────────────────────────
    public function index(Request $request)
    {
        $query = Examen::with('asignatura');

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
            $query->where('nombre', 'ilike', "%{$q}%");
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
            'estado'           => 'required|in:PROGRAMADO,EN_CURSO,FINALIZADO,CANCELADO',
        ]);

        $examen = Examen::create($data);

        return response()->json([
            'message' => 'Examen creado correctamente.',
            'examen'  => $examen->load('asignatura'),
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
            'estado'           => 'sometimes|required|in:PROGRAMADO,EN_CURSO,FINALIZADO,CANCELADO',
        ]);

        $examen->update($data);
        $examen->refresh();

        return response()->json([
            'message' => 'Examen actualizado correctamente.',
            'examen'  => $examen->load('asignatura'),
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

        $examen->delete();

        return response()->json(['message' => 'Examen eliminado correctamente.']);
    }
}
