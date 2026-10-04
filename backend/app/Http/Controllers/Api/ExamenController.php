<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Examen;
use Illuminate\Http\Request;

class ExamenController extends Controller
{
    public function index(Request $request)
    {
        $query = Examen::with('asignatura');

        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }
        if ($request->has('fecha')) {
            $query->whereDate('fecha', $request->fecha);
        }

        return response()->json($query->orderBy('fecha', 'desc')->paginate(20));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_asignatura'    => 'required|exists:asignatura,id_asignatura',
            'nombre'           => 'required|string|max:150',
            'fecha'            => 'required|date',
            'hora_inicio'      => 'required|date_format:H:i',
            'duracion_minutos' => 'required|integer|min:1',
            'descripcion'      => 'nullable|string',
            'estado'           => 'required|in:programado,en_curso,finalizado,cancelado',
        ]);

        $examen = Examen::create($data);

        return response()->json($examen->load('asignatura'), 201);
    }

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
            'estado'           => 'sometimes|required|in:programado,en_curso,finalizado,cancelado',
        ]);

        $examen->update($data);

        return response()->json($examen->load('asignatura'));
    }

    public function destroy(string $id)
    {
        $examen = Examen::findOrFail($id);
        $examen->delete();

        return response()->json(['message' => 'Examen eliminado.']);
    }
}
