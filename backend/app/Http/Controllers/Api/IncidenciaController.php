<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Incidencia;
use Illuminate\Http\Request;

class IncidenciaController extends Controller
{
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
            'tipo'        => 'sometimes|string|max:50',
            'descripcion' => 'sometimes|string',
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
