<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Carrera;
use Illuminate\Http\Request;

class CarreraController extends Controller
{
    public function index(Request $request)
    {
        $query = Carrera::with('facultad')
            ->orderBy('nombre');

        if ($request->filled('id_facultad')) {
            $query->where('id_facultad', $request->id_facultad);
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_facultad' => 'required|integer|exists:facultad,id_facultad',
            'codigo'      => 'required|string|max:30|unique:carrera,codigo',
            'nombre'      => 'required|string|max:150',
        ]);

        $carrera = Carrera::create($data);

        return response()->json(
            $carrera->load('facultad'),
            201
        );
    }

    public function show(string $id)
    {
        return response()->json(
            Carrera::with(['facultad', 'asignaturas', 'estudiantes'])
                ->findOrFail($id)
        );
    }

    public function update(Request $request, string $id)
    {
        $carrera = Carrera::findOrFail($id);

        $data = $request->validate([
            'id_facultad' => 'sometimes|required|integer|exists:facultad,id_facultad',
            'codigo'      => "sometimes|required|string|max:30|unique:carrera,codigo,{$id},id_carrera",
            'nombre'      => 'sometimes|required|string|max:150',
        ]);

        $carrera->update($data);

        return response()->json(
            $carrera->load('facultad')
        );
    }

    public function destroy(string $id)
    {
        Carrera::findOrFail($id)->delete();

        return response()->json([
            'message' => 'Carrera eliminada.',
        ]);
    }
}