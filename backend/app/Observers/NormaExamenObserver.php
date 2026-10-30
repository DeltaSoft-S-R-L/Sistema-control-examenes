<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\NormaExamen;
use Illuminate\Support\Facades\Auth;

class NormaExamenObserver
{
    public function created(NormaExamen $norma): void
    {
        $this->registrar($norma, 'NORMA_CREADA');
    }

    public function deleted(NormaExamen $norma): void
    {
        $this->registrar($norma, 'NORMA_ELIMINADA');
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
