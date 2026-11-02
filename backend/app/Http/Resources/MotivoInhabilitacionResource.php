<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MotivoInhabilitacionResource extends JsonResource
{
    /**
     * Etiquetas legibles en español para cada tipo de motivo (HAB-02 / VER-02).
     */
    public const ETIQUETAS = [
        'ACADEMICO'     => 'Académico',
        'MORA'          => 'Mora',
        'DOCUMENTACION' => 'Documentación',
        'OTRO'          => 'Otro',
    ];

    public static function etiqueta(?string $tipo): ?string
    {
        if (! $tipo) {
            return null;
        }

        $clave = strtoupper(trim($tipo));

        return self::ETIQUETAS[$clave] ?? ucfirst(strtolower($clave));
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $nombreInhabilitador = null;

        if ($this->inhabilitador_nombre !== null || $this->inhabilitador_apellido !== null) {
            $nombreInhabilitador = trim(
                ($this->inhabilitador_nombre ?? '') . ' ' . ($this->inhabilitador_apellido ?? '')
            );
        } elseif ($this->relationLoaded('inhabilitadoPor') && $this->inhabilitadoPor) {
            $nombreInhabilitador = trim(
                ($this->inhabilitadoPor->nombre ?? '') . ' ' . ($this->inhabilitadoPor->apellido ?? '')
            );
        }

        if ($nombreInhabilitador === '') {
            $nombreInhabilitador = null;
        }

        return [
            'tipo'             => $this->motivo_tipo,
            'motivo_tipo'      => $this->motivo_tipo,
            'etiqueta'         => self::etiqueta($this->motivo_tipo),
            'motivo_etiqueta'  => self::etiqueta($this->motivo_tipo),
            'detalle'          => $this->motivo_detalle,
            'motivo_detalle'   => $this->motivo_detalle,
            'inhabilitado_por' => $nombreInhabilitador,
            'inhabilitado_at'  => $this->inhabilitado_at?->toIso8601String(),
        ];
    }
}