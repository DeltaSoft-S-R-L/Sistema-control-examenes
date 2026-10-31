<?php

namespace App\Http\Resources;

use App\Models\Estudiante;
use App\Models\Examen;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VerificacionResource extends JsonResource
{
    /**
     * Desactivar envoltura "data" para responder con la estructura directa.
     */
    public static $wrap = null;

    protected string $metodo;
    protected Estudiante $estudiante;
    protected Examen $examen;

    /**
     * Constructor flexible para aceptar ($metodo, $estudiante, $examen)
     * o un array asociativo ['metodo' => ..., 'estudiante' => ..., 'examen' => ...].
     */
    public function __construct(mixed $resource, ?Estudiante $estudiante = null, ?Examen $examen = null)
    {
        if (is_string($resource) && $estudiante instanceof Estudiante && $examen instanceof Examen) {
            $this->metodo = $resource;
            $this->estudiante = $estudiante;
            $this->examen = $examen;
            parent::__construct(null);
        } elseif (is_array($resource)) {
            $this->metodo = (string) ($resource['metodo'] ?? 'QR');
            $this->estudiante = $resource['estudiante'];
            $this->examen = $resource['examen'];
            parent::__construct($resource);
        } else {
            parent::__construct($resource);
        }
    }

    /**
     * Transformar el recurso a array según el contrato acordado de VER-01.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $fecha = $this->examen->fecha;
        if ($fecha instanceof \DateTimeInterface) {
            $fechaFormatted = $fecha->format('Y-m-d');
        } elseif (!empty($fecha)) {
            $fechaFormatted = substr((string) $fecha, 0, 10);
        } else {
            $fechaFormatted = null;
        }

        return [
            'metodo' => $this->metodo,
            'estudiante' => [
                'id'         => (int) $this->estudiante->id_estudiante,
                'nombres'    => (string) $this->estudiante->nombre,
                'apellidos'  => (string) $this->estudiante->apellido,
                'ci'         => (string) $this->estudiante->ci,
                'codigo_sis' => (string) $this->estudiante->codigo_universitario,
            ],
            'examen' => [
                'id'         => (int) $this->examen->id_examen,
                'asignatura' => (string) ($this->examen->asignatura?->nombre ?? ''),
                'fecha'      => $fechaFormatted,
                'hora'       => (string) $this->examen->hora_inicio,
            ],
        ];
    }
}
