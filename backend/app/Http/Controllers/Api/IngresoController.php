<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ingreso;
use Illuminate\Http\Request;

class IngresoController extends Controller
{
    public function index(Request $request)
    {
        $query = Ingreso::with(['habilitacion.estudiante', 'asignacionAmbiente.ambiente', 'usuario']);

        if ($request->has('id_examen')) {
            $query->where('id_examen', $request->id_examen);
        }

        return response()->json($query->orderBy('fecha_hora', 'desc')->paginate(20));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_examen'              => 'required|exists:examen,id_examen',
            'id_habilitacion'        => 'required|exists:habilitacion,id_habilitacion',
            'id_asignacion_ambiente' => 'required|exists:asignacion_ambiente,id_asignacion_ambiente',
        ]);

        $data['id_usuario'] = $request->user()->id_usuario;
        $data['fecha_hora'] = now();

        $ingreso = Ingreso::create($data);

        return response()->json($ingreso->load(['habilitacion.estudiante', 'usuario']), 201);
    }

    public function show(string $id)
    {
        return response()->json(
            Ingreso::with(['habilitacion.estudiante', 'asignacionAmbiente.ambiente', 'usuario', 'expulsion'])->findOrFail($id)
        );
    }

    public function update(Request $request, string $id)
    {
        return response()->json(['message' => 'No permitido.'], 403);
    }

    public function destroy(string $id)
    {
        return response()->json(['message' => 'No permitido.'], 403);
    }
}
