<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IngresoResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id_ingreso' => $this->id_ingreso,
            'id' => $this->id_ingreso,
            'id_examen' => (int) $this->id_examen,
            'id_habilitacion' => (int) $this->id_habilitacion,
            'id_asignacion_ambiente' => (int) $this->id_asignacion_ambiente,
            'id_usuario' => (int) $this->id_usuario,
            'controlador_id' => (int) $this->id_usuario,
            'fecha_hora' => $this->fecha_hora?->toIso8601String() ?? (string) $this->fecha_hora,
            'habilitacion' => $this->whenLoaded('habilitacion'),
            'asignacion_ambiente' => $this->whenLoaded('asignacionAmbiente'),
            'asignacionAmbiente' => $this->whenLoaded('asignacionAmbiente'),
            'usuario' => $this->whenLoaded('usuario'),
        ];
    }
}
