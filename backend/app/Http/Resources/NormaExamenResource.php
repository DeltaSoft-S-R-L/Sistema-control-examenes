<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NormaExamenResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id_norma_examen,
            'tipo' => $this->tipo,
            'descripcion' => $this->descripcion,
        ];

        if ($this->tipo === 'PARTICULAR' && $this->estudiante) {
            $data['estudiante'] = [
                'id' => $this->estudiante->id_estudiante,
                'nombres' => $this->estudiante->nombre,
                'apellidos' => $this->estudiante->apellido,
                'CI' => $this->estudiante->ci,
            ];
        }

        $data['created_by'] = $this->creador?->nombre;
        $data['created_at'] = $this->created_at?->toISOString() ?? (string) $this->created_at;

        return $data;
    }
}
