<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\NormaExamen;
use App\Services\AuditoriaService;
use Illuminate\Support\Facades\Auth;

class NormaExamenObserver
{
    public function created(NormaExamen $norma): void
    {
        $this->registrar($norma, 'NORMA_CREADA');

        $accionLegacy = $norma->tipo === 'GENERAL' ? 'CREACION_NORMA' : 'CREACION_NORMA_PARTICULAR';
        $entidadLegacy = $norma->tipo === 'GENERAL' ? 'EXAMEN' : 'HABILITACION';
        $idRegistroLegacy = $norma->tipo === 'GENERAL' ? $norma->id_examen : ($norma->id_estudiante ?? $norma->id_examen);
        $usuario = Auth::guard('sanctum')->user() ?? request()->user() ?? Auth::user();
        $idUsuario = $usuario?->id_usuario ?? $usuario?->id ?? null;

        $desc = $norma->tipo === 'GENERAL'
            ? "Norma general agregada al examen ID {$norma->id_examen}: \"{$norma->descripcion}\""
            : "Norma particular para estudiante ID {$norma->id_estudiante} en examen ID {$norma->id_examen}: \"{$norma->descripcion}\"";

        AuditoriaService::registrar(
            $idUsuario,
            $accionLegacy,
            $entidadLegacy,
            $idRegistroLegacy,
            'EXITO',
            $desc
        );
    }

    public function deleted(NormaExamen $norma): void
    {
        $this->registrar($norma, 'NORMA_ELIMINADA');

        $usuario = Auth::guard('sanctum')->user() ?? request()->user() ?? Auth::user();
        $idUsuario = $usuario?->id_usuario ?? $usuario?->id ?? null;

        AuditoriaService::registrar(
            $idUsuario,
            'ELIMINACION_NORMA',
            'EXAMEN',
            $norma->id_examen,
            'EXITO',
            "Norma eliminada del examen ID {$norma->id_examen}: \"{$norma->descripcion}\""
        );
    }

    private function registrar(NormaExamen $norma, string $accion): void
    {
        $usuario = Auth::guard('sanctum')->user() ?? request()->user() ?? Auth::user();
        $idUsuario = $usuario?->id_usuario ?? $usuario?->id ?? null;

        AuditLog::create([
            'id_usuario' => $idUsuario,
            'accion' => $accion,
            'entidad' => $norma->getTable(),
            'id_registro' => (string) $norma->getKey(),
            'fecha_hora' => now(),
            'informacion' => [
                'examen_id' => $norma->id_examen,
                'id_examen' => $norma->id_examen,
                'tipo' => $norma->tipo,
                'estudiante_id' => $norma->id_estudiante,
                'id_estudiante' => $norma->id_estudiante,
                'descripcion' => $norma->descripcion,
            ],
        ]);
    }
}
