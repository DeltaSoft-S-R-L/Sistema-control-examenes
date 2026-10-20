<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExamenRequest;
use App\Http\Requests\UpdateExamenRequest;
use App\Models\AsignacionAmbiente;
use App\Models\Auditoria;
use App\Models\Examen;
use App\Services\AuditoriaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExamenController extends Controller
{
    private function docenteTieneMateria($usuario, int $idAsignatura): bool
    {
        $rol = strtoupper($usuario?->rol?->nombre ?? '');
        if ($rol === 'ADMINISTRADOR') {
            return true;
        }

        if ($rol !== 'DOCENTE') {
            return false;
        }

        return DB::table('docente_asignatura')
            ->where('id_usuario', $usuario->id_usuario)
            ->where('id_asignatura', $idAsignatura)
            ->exists();
    }

    private function escaparLike(string $valor): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $valor);
    }

    // ─────────────────────────────────────────────
    // GET /api/examenes
    // Parámetros opcionales: ?estado=&fecha=&id_asignatura=&buscar=
    // ─────────────────────────────────────────────
    public function index(Request $request)
    {
        $usuario = $request->user();
        $rol = strtoupper($usuario?->rol?->nombre ?? '');

        $query = Examen::with(['asignatura', 'asignacionesAmbiente.ambiente']);

        if ($rol === 'DOCENTE') {
            $query->whereIn('id_asignatura', function ($sub) use ($usuario) {
                $sub->select('id_asignatura')
                    ->from('docente_asignatura')
                    ->where('id_usuario', $usuario->id_usuario);
            });
        }

        if ($request->filled('estado')) {
            $query->where('estado', strtoupper($request->estado));
        }

        if ($request->filled('fecha')) {
            $query->whereDate('fecha', $request->fecha);
        }

        if ($request->filled('id_asignatura')) {
            $query->where('id_asignatura', $request->id_asignatura);
        }

        if ($request->filled('buscar')) {
            $q = $this->escaparLike($request->buscar);
            $like = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where(function ($sub) use ($q, $like) {
                $sub->where('nombre', $like, "%{$q}%")
                    ->orWhere('descripcion', $like, "%{$q}%");
            });
        }

        return response()->json($query->orderBy('fecha', 'desc')->paginate(20));
    }

    // ─────────────────────────────────────────────
    // POST /api/examenes (Crear Examen con Auditoría)
    // ─────────────────────────────────────────────
    public function store(StoreExamenRequest $request)
    {
        $usuario = $request->user();
        $data = $request->validated();

        if (!$this->docenteTieneMateria($usuario, (int) $data['id_asignatura'])) {
            return response()->json([
                'message' => 'No tiene asignada esta materia para programar exámenes.',
            ], 403);
        }

        $examen = DB::transaction(function () use ($data, $request) {
            $idAmbiente = $data['id_ambiente'] ?? null;
            unset($data['id_ambiente']);

            $examen = Examen::create($data);

            if ($idAmbiente) {
                AsignacionAmbiente::create([
                    'id_examen' => $examen->id_examen,
                    'id_ambiente' => $idAmbiente,
                ]);
            }

            $examenCargado = $examen->load(['asignatura', 'asignacionesAmbiente.ambiente']);
            $ambienteInfo = $examenCargado->asignacionesAmbiente->first()?->ambiente?->nombre ?? 'Sin ambiente';

            AuditoriaService::registrar(
                $request->user()?->id_usuario,
                'CREACION',
                'EXAMEN',
                $examen->id_examen,
                'EXITO',
                "Creación de examen '{$examen->nombre}' (Fecha: {$examen->fecha->format('Y-m-d')}, Hora: {$examen->hora_inicio}, Duración: {$examen->duracion_minutos}m, Estado: {$examen->estado}, Ambiente: {$ambienteInfo})."
            );

            return $examenCargado;
        });

        return response()->json([
            'message' => 'Examen creado correctamente.',
            'examen' => $examen,
        ], 201);
    }

    // ─────────────────────────────────────────────
    // GET /api/examenes/{id}
    // ─────────────────────────────────────────────
    public function show(Request $request, string $id)
    {
        $usuario = $request->user();
        $examen = Examen::with([
            'asignatura',
            'asignacionesAmbiente.ambiente',
            'reglas',
            'habilitaciones.estudiante',
        ])->findOrFail($id);

        if (!$this->docenteTieneMateria($usuario, (int) $examen->id_asignatura)) {
            return response()->json([
                'message' => 'No tiene permisos para consultar este examen.',
            ], 403);
        }

        return response()->json($examen);
    }

    // ─────────────────────────────────────────────
    // PUT/PATCH /api/examenes/{id} (Modificar Examen con Auditoría)
    // ─────────────────────────────────────────────
    public function update(UpdateExamenRequest $request, string $id)
    {
        $usuario = $request->user();
        $examen = Examen::findOrFail($id);

        if (!$this->docenteTieneMateria($usuario, (int) $examen->id_asignatura)) {
            return response()->json([
                'message' => 'No tiene permisos para modificar este examen.',
            ], 403);
        }

        $data = $request->validated();
        if (isset($data['id_asignatura']) && !$this->docenteTieneMateria($usuario, (int) $data['id_asignatura'])) {
            return response()->json([
                'message' => 'No tiene asignada la materia seleccionada.',
            ], 403);
        }

        // Capturar valores previos para el registro de auditoría
        $valoresPrevios = [
            'nombre'           => $examen->nombre,
            'fecha'            => $examen->fecha ? $examen->fecha->format('Y-m-d') : null,
            'hora_inicio'      => $examen->hora_inicio,
            'duracion_minutos' => $examen->duracion_minutos,
            'estado'           => $examen->estado,
            'id_asignatura'    => $examen->id_asignatura,
        ];

        if (array_key_exists('id_ambiente', $data)) {
            $idAmbiente = $data['id_ambiente'];
            unset($data['id_ambiente']);

            AsignacionAmbiente::where('id_examen', $examen->id_examen)->delete();

            if ($idAmbiente) {
                AsignacionAmbiente::create([
                    'id_examen'   => $examen->id_examen,
                    'id_ambiente' => $idAmbiente,
                ]);
            }
        }

        $examen->update($data);
        $examen->refresh();

        // ── Detectar cambios para la descripción de auditoría ──
        $cambios = [];
        foreach ($data as $campo => $valorNuevo) {
            $valorPrevio = $valoresPrevios[$campo] ?? null;
            if ($valorPrevio != $valorNuevo) {
                $cambios[] = "{$campo}: '{$valorPrevio}' => '{$valorNuevo}'";
            }
        }
        $detalleCambios = !empty($cambios) ? implode(', ', $cambios) : 'Actualización de datos generales';

        // ── Registro de Auditoría: Modificación ──
        AuditoriaService::registrar(
            $request->user()?->id_usuario,
            'MODIFICACION',
            'EXAMEN',
            $examen->id_examen,
            'EXITO',
            "Modificación de examen '{$examen->nombre}' (ID: {$examen->id_examen}). Cambios: [{$detalleCambios}]."
        );

        return response()->json([
            'message' => 'Examen actualizado correctamente.',
            'examen'  => $examen->load(['asignatura', 'asignacionesAmbiente.ambiente']),
        ]);
    }

    // ─────────────────────────────────────────────
    // DELETE /api/examenes/{id} (Eliminar Examen con Auditoría)
    // ─────────────────────────────────────────────
    public function destroy(Request $request, string $id)
    {
        $usuario = $request->user();
        $examen = Examen::findOrFail($id);

        if (!$this->docenteTieneMateria($usuario, (int) $examen->id_asignatura)) {
            return response()->json([
                'message' => 'No tiene permisos para eliminar este examen.',
            ], 403);
        }

        if (!in_array($examen->estado, ['PROGRAMADO', 'CANCELADO'])) {
            // ── Registro de Auditoría: Intento fallido de eliminación ──
            AuditoriaService::registrar(
                $request->user()?->id_usuario,
                'ELIMINACION',
                'EXAMEN',
                $examen->id_examen,
                'FALLO',
                "Intento rechazado de eliminar examen '{$examen->nombre}' en estado '{$examen->estado}'. Solo se permite eliminar en PROGRAMADO o CANCELADO."
            );

            return response()->json([
                'message' => "No se puede eliminar un examen en estado \"{$examen->estado}\". Solo se permiten eliminar exámenes en estado PROGRAMADO o CANCELADO.",
            ], 409);
        }

        $nombreExamen = $examen->nombre;
        $estadoExamen = $examen->estado;
        $idExamen = $examen->id_examen;

        // Eliminar asignaciones de ambiente asociadas
        AsignacionAmbiente::where('id_examen', $idExamen)->delete();

        $examen->delete();

        // ── Registro de Auditoría: Eliminación exitosa ──
        AuditoriaService::registrar(
            $request->user()?->id_usuario,
            'ELIMINACION',
            'EXAMEN',
            $idExamen,
            'EXITO',
            "Eliminación exitosa del examen '{$nombreExamen}' (ID: {$idExamen}) que se encontraba en estado '{$estadoExamen}'."
        );

        return response()->json(['message' => 'Examen eliminado correctamente.']);
    }

    // ─────────────────────────────────────────────
    // GET /api/examenes/{id}/auditoria
    // Consulta del historial de auditoría de un examen específico
    // ─────────────────────────────────────────────
    public function auditoria(string $id)
    {
        $auditorias = Auditoria::with('usuario:id_usuario,username,nombre,apellido')
            ->where('entidad', 'EXAMEN')
            ->where('id_registro', $id)
            ->orderBy('fecha_hora', 'desc')
            ->get();

        return response()->json($auditorias);
    }
}
