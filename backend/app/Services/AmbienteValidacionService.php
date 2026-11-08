<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AsignacionAmbiente;
use App\Models\Habilitacion;
use App\Models\IntentoIngreso;
use Illuminate\Support\Facades\Log;

class AmbienteValidacionService
{
    /**
     * Valida si el ambiente de la puerta corresponde al ambiente asignado a la habilitación (ING-04).
     *
     * @return array{valido: bool, ambiente_asignado: array<string, mixed>|null}
     */
    public function validarCorrespondencia(
        Habilitacion $habilitacion,
        int $idAsignacionAmbienteActual,
        int $idUsuario
    ): array {
        // Compatibilidad hacia atrás: si no tiene aula individual asignada, se permite ingresar en cualquier ambiente válido del examen
        if ($habilitacion->id_asignacion_ambiente === null) {
            return [
                'valido'            => true,
                'ambiente_asignado' => null,
            ];
        }

        // Si el ambiente donde está la puerta coincide con el asignado
        if ((int) $habilitacion->id_asignacion_ambiente === $idAsignacionAmbienteActual) {
            $asignacion = $habilitacion->relationLoaded('asignacionAmbiente')
                ? $habilitacion->asignacionAmbiente
                : AsignacionAmbiente::with('ambiente')->find($habilitacion->id_asignacion_ambiente);

            return [
                'valido'            => true,
                'ambiente_asignado' => $this->formatearAmbiente($asignacion),
            ];
        }

        // DISCREPANCIA DETECTADA: El estudiante tiene asignada otra aula
        $asignacionCorrecta = AsignacionAmbiente::with('ambiente')->find($habilitacion->id_asignacion_ambiente);
        $ambienteData = $this->formatearAmbiente($asignacionCorrecta);

        // 1. Persistir anomalía en intento_ingreso con motivo = 'AULA_INCORRECTA'
        try {
            IntentoIngreso::create([
                'id_estudiante'          => $habilitacion->id_estudiante,
                'id_examen'              => (int) $habilitacion->id_examen,
                'id_asignacion_ambiente' => $idAsignacionAmbienteActual,
                'id_usuario'             => $idUsuario,
                'fecha_hora'             => now(),
                'motivo'                 => 'AULA_INCORRECTA',
            ]);
        } catch (\Throwable $e) {
            Log::error('Error al registrar intento con aula incorrecta: ' . $e->getMessage(), [
                'id_habilitacion' => $habilitacion->id_habilitacion,
                'exception'       => $e,
            ]);
        }

        // 2. Registrar en audit_logs mediante AuditService
        try {
            AuditService::registrar(
                accion: 'DISCREPANCIA_AMBIENTE_INGRESO',
                entidad: 'ingreso',
                idRegistro: (string) $habilitacion->id_habilitacion,
                informacion: [
                    'id_estudiante'                   => $habilitacion->id_estudiante,
                    'id_examen'                       => (int) $habilitacion->id_examen,
                    'id_asignacion_ambiente_puerta'   => $idAsignacionAmbienteActual,
                    'id_asignacion_ambiente_asignada' => (int) $habilitacion->id_asignacion_ambiente,
                    'ambiente_asignado'               => $ambienteData,
                    'motivo'                          => 'AULA_INCORRECTA',
                ],
                idUsuario: $idUsuario
            );
        } catch (\Throwable $e) {
            Log::error('Error al registrar auditoría de discrepancia de ambiente: ' . $e->getMessage(), [
                'id_habilitacion' => $habilitacion->id_habilitacion,
                'exception'       => $e,
            ]);
        }

        return [
            'valido'            => false,
            'ambiente_asignado' => $ambienteData,
        ];
    }

    /**
     * Formatear los datos del ambiente asignado para la respuesta estructurada de discrepancia.
     *
     * @return array<string, mixed>|null
     */
    public function formatearAmbiente(?AsignacionAmbiente $asignacion): ?array
    {
        if (!$asignacion || !$asignacion->ambiente) {
            return null;
        }

        return [
            'id_ambiente'            => (int) $asignacion->ambiente->id_ambiente,
            'id_asignacion_ambiente' => (int) $asignacion->id_asignacion_ambiente,
            'codigo'                 => (string) $asignacion->ambiente->codigo,
            'nombre'                 => (string) $asignacion->ambiente->nombre,
            'ubicacion'              => (string) $asignacion->ambiente->ubicacion,
        ];
    }
}
