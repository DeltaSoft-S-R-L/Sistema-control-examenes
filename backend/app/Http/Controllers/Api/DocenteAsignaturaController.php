<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;


class DocenteAsignaturaController extends Controller
{
    private function verificarDocente(Usuario $usuario): void
    {
        $usuario->loadMissing('rol');

        if (strtoupper($usuario->rol?->nombre ?? '') !== 'DOCENTE') {
            throw ValidationException::withMessages([
                'docente' => 'El usuario seleccionado no tiene el rol DOCENTE.',
            ]);
        }
    }

    public function index(Usuario $usuario)
    {
        $this->verificarDocente($usuario);

        $materias = DB::table('docente_asignatura as da')
            ->join('asignatura as a', 'a.id_asignatura', '=', 'da.id_asignatura')
            ->join('carrera as c', 'c.id_carrera', '=', 'da.id_carrera')
            ->where('da.id_usuario', $usuario->id_usuario)
            ->select(
                'da.id_carrera',
                'c.nombre as carrera',
                'da.id_asignatura',
                'a.codigo',
                'a.nombre as asignatura',
                'da.fecha_asignacion'
            )
            ->orderBy('c.nombre')
            ->orderBy('a.nombre')
            ->get();

        return response()->json([
            'docente' => $usuario->only('id_usuario', 'nombre', 'apellido'),
            'materias' => $materias,
        ]);
    }

    public function update(Request $request, Usuario $usuario)
    {
        $this->verificarDocente($usuario);

        $datos = $request->validate([
            'materias' => ['required', 'array'],
            'materias.*.id_carrera' => ['required', 'integer', 'exists:carrera,id_carrera'],
            'materias.*.id_asignatura' => ['required', 'integer', 'exists:asignatura,id_asignatura'],
        ]);

        $materias = collect($datos['materias'])
            ->map(fn ($materia) => [
                'id_carrera' => (int) $materia['id_carrera'],
                'id_asignatura' => (int) $materia['id_asignatura'],
            ])
            ->unique(fn ($materia) => $materia['id_carrera'] . ':' . $materia['id_asignatura'])
            ->values();

        foreach ($materias as $materia) {
            $existe = DB::table('carrera_asignatura')
                ->where('id_carrera', $materia['id_carrera'])
                ->where('id_asignatura', $materia['id_asignatura'])
                ->exists();

            if (!$existe) {
                throw ValidationException::withMessages([
                    'materias' => 'Una de las materias no pertenece a la carrera indicada.',
                ]);
            }
        }

        DB::transaction(function () use ($usuario, $materias) {
            DB::table('docente_asignatura')
                ->where('id_usuario', $usuario->id_usuario)
                ->delete();

            if ($materias->isNotEmpty()) {
                DB::table('docente_asignatura')->insert(
                    $materias->map(fn ($materia) => [
                        'id_usuario' => $usuario->id_usuario,
                        'id_carrera' => $materia['id_carrera'],
                        'id_asignatura' => $materia['id_asignatura'],
                        'fecha_asignacion' => now(),
                    ])->all()
                );
            }
        });

        return response()->json([
            'message' => 'Materias del docente actualizadas correctamente.',
            'total' => $materias->count(),
        ]);
    }
}
