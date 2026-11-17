<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Examen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReporteController extends Controller
{
    public function examenes(Request $request)
    {
        $usuario = $request->user();
        $query = Examen::withTrashed()->with('asignatura')->orderByDesc('fecha')->orderBy('hora_inicio');

        if (strtoupper((string) $usuario?->rol?->nombre) === 'DOCENTE') {
            $query->whereIn('id_asignatura', function ($sub) use ($usuario) {
                $sub->select('id_asignatura')->from('docente_asignatura')
                    ->where('id_usuario', $usuario->id_usuario);
            });
        }

        return response()->json($query->get(['id_examen', 'id_asignatura', 'nombre', 'fecha', 'hora_inicio', 'estado']));
    }

    public function asistencia(Request $request, int $id)
    {
        $examen = $this->examenAutorizado($request, $id);
        if ($examen instanceof \Illuminate\Http\JsonResponse) return $examen;

        $asignacionAmbiente = Schema::hasColumn('habilitacion', 'id_asignacion_ambiente')
            ? DB::raw('COALESCE(h.id_asignacion_ambiente, i.id_asignacion_ambiente)')
            : DB::raw('i.id_asignacion_ambiente');

        $rows = DB::table('habilitacion as h')
            ->join('estudiante as e', 'e.id_estudiante', '=', 'h.id_estudiante')
            ->leftJoin('ingreso as i', 'i.id_habilitacion', '=', 'h.id_habilitacion')
            ->leftJoin('asignacion_ambiente as aa', fn ($join) => $join->on('aa.id_asignacion_ambiente', '=', $asignacionAmbiente))
            ->leftJoin('ambiente as a', 'a.id_ambiente', '=', 'aa.id_ambiente')
            ->leftJoin('expulsion as ex', 'ex.id_ingreso', '=', 'i.id_ingreso')
            ->where('h.id_examen', $id)
            ->orderBy('e.apellido')->orderBy('e.nombre');

        if (strtoupper((string) $request->user()?->rol?->nombre) === 'DOCENTE') {
            $idsEstudiantes = DB::table('docente_asignatura as da')
                ->join('estudiante as e', 'e.id_carrera', '=', 'da.id_carrera')
                ->where('da.id_usuario', $request->user()->id_usuario)
                ->where('da.id_asignatura', $examen->id_asignatura)
                ->select('e.id_estudiante');
            $rows->whereIn('h.id_estudiante', $idsEstudiantes);
        }

        $rows = $rows->get([
                'h.id_habilitacion', 'h.estado as estado_habilitacion',
                'e.id_estudiante', 'e.nombre', 'e.apellido', 'e.ci', 'e.codigo_universitario',
                'i.id_ingreso', 'i.fecha_hora as fecha_hora_ingreso',
                'a.nombre as ambiente', 'a.codigo as codigo_ambiente',
                'ex.id_expulsion', 'ex.motivo as motivo_expulsion', 'ex.fecha_hora as fecha_hora_expulsion',
            ])->map(function ($row) {
                $row->asistencia = $row->id_ingreso ? ($row->id_expulsion ? 'EXPULSADO' : 'PRESENTE') : 'AUSENTE';
                return $row;
            });

        return response()->json([
            'examen' => ['id_examen' => $examen->id_examen, 'nombre' => $examen->nombre, 'fecha' => $examen->fecha, 'asignatura' => $examen->asignatura?->nombre],
            'resumen' => [
                'habilitados' => $rows->count(),
                'presentes' => $rows->whereIn('asistencia', ['PRESENTE', 'EXPULSADO'])->count(),
                'ausentes' => $rows->where('asistencia', 'AUSENTE')->count(),
                'expulsados' => $rows->where('asistencia', 'EXPULSADO')->count(),
            ],
            'data' => $rows->values(),
        ]);
    }

    public function intentos(Request $request, int $id)
    {
        $examen = $this->examenAutorizado($request, $id);
        if ($examen instanceof \Illuminate\Http\JsonResponse) return $examen;

        $intentos = DB::table('intento_ingreso as ii')
            ->leftJoin('estudiante as e', 'e.id_estudiante', '=', 'ii.id_estudiante')
            ->leftJoin('usuario as u', 'u.id_usuario', '=', 'ii.id_usuario')
            ->leftJoin('asignacion_ambiente as aa', 'aa.id_asignacion_ambiente', '=', 'ii.id_asignacion_ambiente')
            ->leftJoin('ambiente as a', 'a.id_ambiente', '=', 'aa.id_ambiente')
            ->where('ii.id_examen', $id)
            ->orderByDesc('ii.fecha_hora');

        $incidenciasQuery = DB::table('incidencia as i')
            ->leftJoin('estudiante as e', 'e.id_estudiante', '=', 'i.id_estudiante')
            ->leftJoin('usuario as u', 'u.id_usuario', '=', 'i.id_usuario')
            ->where('i.id_examen', $id)
            ->orderByDesc('i.fecha_hora');

        if (strtoupper((string) $request->user()?->rol?->nombre) === 'DOCENTE') {
            $idsEstudiantes = DB::table('docente_asignatura as da')
                ->join('estudiante as est', 'est.id_carrera', '=', 'da.id_carrera')
                ->where('da.id_usuario', $request->user()->id_usuario)
                ->where('da.id_asignatura', $examen->id_asignatura)
                ->select('est.id_estudiante');
            $intentos->whereIn('ii.id_estudiante', $idsEstudiantes);
            $incidenciasQuery->where(function ($query) use ($idsEstudiantes) {
                $query->whereNull('i.id_estudiante')->orWhereIn('i.id_estudiante', $idsEstudiantes);
            });
        }

        $intentos = $intentos->get(['ii.id_intento', 'ii.fecha_hora', 'ii.motivo', 'e.id_estudiante', 'e.nombre', 'e.apellido', 'e.ci', 'e.codigo_universitario', 'u.nombre as operador', 'u.apellido as apellido_operador', 'a.nombre as ambiente', 'a.codigo as codigo_ambiente']);

        $incidencias = $incidenciasQuery->get(['i.id_incidencia', 'i.fecha_hora', 'i.tipo', 'i.descripcion', 'e.id_estudiante', 'e.nombre', 'e.apellido', 'u.nombre as operador', 'u.apellido as apellido_operador'])
            ->map(fn ($row) => (object) ((array) $row + ['motivo' => $row->tipo]));

        $data = $intentos->concat($incidencias)->sortByDesc('fecha_hora')->values();
        $expulsiones = DB::table('expulsion as ex')
            ->join('ingreso as i', 'i.id_ingreso', '=', 'ex.id_ingreso')
            ->join('habilitacion as h', 'h.id_habilitacion', '=', 'i.id_habilitacion')
            ->where('i.id_examen', $id);
        if (strtoupper((string) $request->user()?->rol?->nombre) === 'DOCENTE') {
            $idsEstudiantes = DB::table('docente_asignatura as da')
                ->join('estudiante as e', 'e.id_carrera', '=', 'da.id_carrera')
                ->where('da.id_usuario', $request->user()->id_usuario)
                ->where('da.id_asignatura', $examen->id_asignatura)
                ->select('e.id_estudiante');
            $expulsiones->whereIn('h.id_estudiante', $idsEstudiantes);
        }

        return response()->json([
            'examen' => ['id_examen' => $examen->id_examen, 'nombre' => $examen->nombre, 'fecha' => $examen->fecha],
            'resumen' => [
                'intentos_rechazados' => $intentos->count(),
                'incidencias' => $incidencias->count(),
                'expulsiones' => $expulsiones->count(),
            ],
            'data' => $data,
        ]);
    }

    private function examenAutorizado(Request $request, int $id): Examen|\Illuminate\Http\JsonResponse
    {
        $examen = Examen::withTrashed()->with('asignatura')->find($id);
        if (!$examen) return response()->json(['message' => 'Examen no encontrado.'], 404);

        $usuario = $request->user();
        if (strtoupper((string) $usuario?->rol?->nombre) === 'DOCENTE' && !DB::table('docente_asignatura')
            ->where('id_usuario', $usuario->id_usuario)->where('id_asignatura', $examen->id_asignatura)->exists()) {
            return response()->json(['message' => 'No tiene permisos para consultar este examen.'], 403);
        }

        return $examen;
    }
}
