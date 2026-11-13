<?php

namespace App\Services;

use App\Models\Estudiante;
use App\Models\Examen;
use App\Models\Habilitacion;
use App\Models\Ingreso;

class HabilitacionService
{
    /**
     * Consulta el estado de habilitación de un estudiante para una sesión de examen específica.
     *
     * @return array{estado: string, habilitado: bool, motivo: ?string, id_habilitacion: ?int, ingreso_previo?: array{fecha_hora: ?string, ambiente: ?string}}
     */
    public static function verificarHabilitacion(Estudiante $estudiante, Examen $examen): array
    {
        $habilitacion = Habilitacion::where('id_estudiante', $estudiante->id_estudiante)
            ->where('id_examen', $examen->id_examen)
            ->first();

        // 1. Estudiante no asociado al examen
        if (!$habilitacion) {
            return [
                'estado'          => 'NO_ASOCIADO',
                'habilitado'      => false,
                'motivo'          => 'El estudiante no está asociado a este examen.',
                'id_habilitacion' => null,
            ];
        }

        $estado = strtoupper($habilitacion->estado ?? '');

        $ingresoPrevio = Ingreso::with('asignacionAmbiente.ambiente')
            ->where('id_habilitacion', $habilitacion->id_habilitacion)
            ->where('id_examen', $examen->id_examen)
            ->orderBy('fecha_hora')
            ->first();

        // Un ingreso previo siempre debe advertirse, aunque luego cambie el
        // estado de habilitación del estudiante.
        if ($ingresoPrevio) {
            return [
                'estado'          => 'REINGRESO',
                'habilitado'      => false,
                'motivo'          => 'El estudiante ya registró un ingreso para este examen.',
                'id_habilitacion' => $habilitacion->id_habilitacion,
                'ingreso_previo'  => [
                    'fecha_hora' => $ingresoPrevio->fecha_hora?->toIso8601String(),
                    'ambiente'   => $ingresoPrevio->asignacionAmbiente?->ambiente?->nombre,
                ],
            ];
        }

        // 2. Estudiante inhabilitado con motivo
        if ($estado === 'NO_HABILITADO') {
            return [
                'estado'          => 'NO_HABILITADO',
                'habilitado'      => false,
                'motivo'          => $habilitacion->motivo_detalle ?: $habilitacion->motivo ?: 'Inhabilitado para rendir este examen.',
                'id_habilitacion' => $habilitacion->id_habilitacion,
            ];
        }

        // 3. Estudiante habilitado
        if ($estado === 'HABILITADO') {
            return [
                'estado'          => 'HABILITADO',
                'habilitado'      => true,
                'motivo'          => null,
                'id_habilitacion' => $habilitacion->id_habilitacion,
            ];
        }

        // En caso de cualquier otro estado imprevisto
        return [
            'estado'          => $estado ?: 'NO_HABILITADO',
            'habilitado'      => false,
            'motivo'          => $habilitacion->motivo ?: 'Estado de habilitación no válido.',
            'id_habilitacion' => $habilitacion->id_habilitacion,
        ];
    }
}
