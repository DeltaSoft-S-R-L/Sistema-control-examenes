<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asignatura;
use Illuminate\Http\Request;

class AsignaturaController extends Controller
{
    public function index(Request $request)
    {
        $usuario = $request->user();
        $usuario?->loadMissing('rol');
        $rol = strtoupper((string) $usuario?->rol?->nombre);

        $query = Asignatura::orderBy('nombre');

        if ($rol === 'DOCENTE') {
            $query->whereIn('id_asignatura', function ($sub) use ($usuario) {
                $sub->select('id_asignatura')
                    ->from('docente_asignatura')
                    ->where('id_usuario', $usuario->id_usuario);
            });
        }

        if ($request->filled('id_carrera')) {
            $query->whereHas('carreras', function ($q) use ($request) {
                $q->where('carrera.id_carrera', $request->id_carrera);
            });
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'codigo'      => 'required|string|max:30|unique:asignatura,codigo',
            'nombre'      => 'required|string|max:150',
            'descripcion' => 'nullable|string',
        ]);

        return response()->json(Asignatura::create($data), 201);
    }

    public function show(string $id)
    {
        return response()->json(Asignatura::with('examenes')->findOrFail($id));
    }

    public function update(Request $request, string $id)
    {
        $asignatura = Asignatura::findOrFail($id);

        $data = $request->validate([
            'codigo'      => "sometimes|required|string|max:30|unique:asignatura,codigo,{$id},id_asignatura",
            'nombre'      => 'sometimes|required|string|max:150',
            'descripcion' => 'nullable|string',
        ]);

        $asignatura->update($data);

        return response()->json($asignatura);
    }

    public function destroy(string $id)
    {
        Asignatura::findOrFail($id)->delete();

        return response()->json(['message' => 'Asignatura eliminada.']);
    }
}
