<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Habilitacion;
use Illuminate\Http\Request;

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

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_estudiante' => 'required|exists:estudiante,id_estudiante',
            'id_examen'     => 'required|exists:examen,id_examen',
            'estado'        => 'required|in:habilitado,inhabilitado,pendiente',
            'motivo'        => 'nullable|string',
        ]);

        $habilitacion = Habilitacion::create($data);

        return response()->json($habilitacion->load(['estudiante', 'examen']), 201);
    }

    public function show(string $id)
    {
        return response()->json(
            Habilitacion::with(['estudiante', 'examen', 'reglasIndividuales'])->findOrFail($id)
        );
    }

    public function update(Request $request, string $id)
    {
        $habilitacion = Habilitacion::findOrFail($id);

        $data = $request->validate([
            'estado' => 'sometimes|in:habilitado,inhabilitado,pendiente',
            'motivo' => 'nullable|string',
        ]);

        $habilitacion->update($data);

        return response()->json($habilitacion);
    }

    public function destroy(string $id)
    {
        Habilitacion::findOrFail($id)->delete();

        return response()->json(['message' => 'Habilitación eliminada.']);
    }
}
