<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Facultad;
use Illuminate\Http\Request;

class FacultadController extends Controller
{
    public function index()
    {
        return response()->json(
            Facultad::orderBy('nombre')->get()
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'codigo' => 'required|string|max:30|unique:facultad,codigo',
            'nombre' => 'required|string|max:150',
        ]);

        return response()->json(
            Facultad::create($data),
            201
        );
    }

    public function show(string $id)
    {
        return response()->json(
            Facultad::with('carreras')->findOrFail($id)
        );
    }

    public function update(Request $request, string $id)
    {
        $facultad = Facultad::findOrFail($id);

        $data = $request->validate([
            'codigo' => "sometimes|required|string|max:30|unique:facultad,codigo,{$id},id_facultad",
            'nombre' => 'sometimes|required|string|max:150',
        ]);

        $facultad->update($data);

        return response()->json($facultad);
    }

    public function destroy(string $id)
    {
        Facultad::findOrFail($id)->delete();

        return response()->json([
            'message' => 'Facultad eliminada.',
        ]);
    }
}