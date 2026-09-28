<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Estudiante;
use Illuminate\Http\Request;

class EstudianteController extends Controller
{
    public function index(Request $request)
    {
        $query = Estudiante::query();

        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }
        if ($request->has('buscar')) {
            $q = $request->buscar;
            $query->where(function ($q2) use ($q) {
                $q2->where('nombre', 'ilike', "%{$q}%")
                    ->orWhere('apellido', 'ilike', "%{$q}%")
                    ->orWhere('ci', 'ilike', "%{$q}%")
                    ->orWhere('codigo_universitario', 'ilike', "%{$q}%");
            });
        }

        return response()->json($query->orderBy('apellido')->paginate(20));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'ci'                   => 'required|string|max:20|unique:estudiante,ci',
            'nombre'               => 'required|string|max:100',
            'apellido'             => 'required|string|max:100',
            'codigo_universitario' => 'required|string|max:50|unique:estudiante,codigo_universitario',
            'correo'               => 'nullable|email|max:150',
            'estado'               => 'required|in:activo,inactivo,suspendido',
        ]);

        $estudiante = Estudiante::create($data);

        return response()->json($estudiante, 201);
    }

    public function show(string $id)
    {
        $estudiante = Estudiante::with(['habilitaciones.examen'])->findOrFail($id);

        return response()->json($estudiante);
    }

    public function update(Request $request, string $id)
    {
        $estudiante = Estudiante::findOrFail($id);

        $data = $request->validate([
            'ci'                   => "sometimes|required|string|max:20|unique:estudiante,ci,{$id},id_estudiante",
            'nombre'               => 'sometimes|required|string|max:100',
            'apellido'             => 'sometimes|required|string|max:100',
            'codigo_universitario' => "sometimes|required|string|max:50|unique:estudiante,codigo_universitario,{$id},id_estudiante",
            'correo'               => 'nullable|email|max:150',
            'estado'               => 'sometimes|required|in:activo,inactivo,suspendido',
        ]);

        $estudiante->update($data);

        return response()->json($estudiante);
    }

    public function destroy(string $id)
    {
        $estudiante = Estudiante::findOrFail($id);
        $estudiante->delete();

        return response()->json(['message' => 'Estudiante eliminado.']);
    }
}
