<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ambiente;
use Illuminate\Http\Request;

class AmbienteController extends Controller
{
    public function index(Request $request)
    {
        $query = Ambiente::query();

        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        return response()->json($query->orderBy('nombre')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'codigo'    => 'required|string|max:30|unique:ambiente,codigo',
            'nombre'    => 'required|string|max:100',
            'ubicacion' => 'nullable|string|max:150',
            'capacidad' => 'required|integer|min:1',
            'estado'    => 'required|in:disponible,ocupado,mantenimiento',
        ]);

        return response()->json(Ambiente::create($data), 201);
    }

    public function show(string $id)
    {
        return response()->json(Ambiente::findOrFail($id));
    }

    public function update(Request $request, string $id)
    {
        $ambiente = Ambiente::findOrFail($id);

        $data = $request->validate([
            'codigo'    => "sometimes|string|max:30|unique:ambiente,codigo,{$id},id_ambiente",
            'nombre'    => 'sometimes|string|max:100',
            'ubicacion' => 'nullable|string|max:150',
            'capacidad' => 'sometimes|integer|min:1',
            'estado'    => 'sometimes|in:disponible,ocupado,mantenimiento',
        ]);

        $ambiente->update($data);

        return response()->json($ambiente);
    }

    public function destroy(string $id)
    {
        Ambiente::findOrFail($id)->delete();

        return response()->json(['message' => 'Ambiente eliminado.']);
    }
}
