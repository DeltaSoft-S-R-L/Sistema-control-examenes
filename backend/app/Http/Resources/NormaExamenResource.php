<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NormaExamenResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        $id = $this->id_norma_examen ?? $this->id_norma ?? $this->id_regla_examen ?? $this->id_regla_individual;

        $data = [
            'id' => $id,
            'id_norma' => $id,
            'tipo' => $this->tipo,
            'descripcion' => $this->descripcion,
            'estado' => $this->estado ?? 'ACTIVA',
            'id_habilitacion' => $this->id_habilitacion ?? null,
        ];

        if ($this->tipo === 'PARTICULAR' && $this->estudiante) {
            $est = $this->estudiante;
            $data['estudiante'] = [
                'id' => $est->id_estudiante,
                'id_estudiante' => $est->id_estudiante,
                'nombre' => $est->nombre,
                'nombres' => $est->nombre,
                'apellido' => $est->apellido,
                'apellidos' => $est->apellido,
                'nombre_completo' => "{$est->nombre} {$est->apellido}",
                'ci' => $est->ci,
                'CI' => $est->ci,
                'codigo_universitario' => $est->codigo_universitario,
            ];
        }

        $data['created_by'] = $this->creador?->nombre;
        $data['created_at'] = $this->created_at?->toISOString() ?? (string) $this->created_at;

        return $data;
    }
}
