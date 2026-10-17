<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ambiente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AmbienteController extends Controller
{
    public function index(Request $request)
    {
        $query = Ambiente::query();

        if ($request->filled('estado')) {
            $estado = strtoupper($request->estado);
            $query->where(function ($q) use ($estado) {
                $q->where('estado', $estado)
                  ->orWhere('estado', strtolower($estado));
            });
        }

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $like = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where(function ($q) use ($buscar, $like) {
                $q->where('nombre', $like, "%{$buscar}%")
                  ->orWhere('codigo', $like, "%{$buscar}%")
                  ->orWhere('ubicacion', $like, "%{$buscar}%");
            });
        }

        if ($request->filled('capacidad_min')) {
            $query->where('capacidad', '>=', (int) $request->capacidad_min);
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
            'estado'    => 'required|in:DISPONIBLE,NO_DISPONIBLE,disponible,ocupado,mantenimiento',
        ]);

        $data['estado'] = strtoupper($data['estado']);

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
            'codigo'    => "sometimes|required|string|max:30|unique:ambiente,codigo,{$id},id_ambiente",
            'nombre'    => 'sometimes|required|string|max:100',
            'ubicacion' => 'nullable|string|max:150',
            'capacidad' => 'sometimes|required|integer|min:1',
            'estado'    => 'sometimes|required|in:DISPONIBLE,NO_DISPONIBLE,disponible,ocupado,mantenimiento',
        ]);

        if (isset($data['estado'])) {
            $data['estado'] = strtoupper($data['estado']);
        }

        $ambiente->update($data);

        return response()->json($ambiente);
    }

    public function destroy(string $id)
    {
        Ambiente::findOrFail($id)->delete();

        return response()->json(['message' => 'Ambiente eliminado.']);
    }
}
