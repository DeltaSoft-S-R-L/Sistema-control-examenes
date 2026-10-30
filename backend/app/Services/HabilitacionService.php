<?php

namespace App\Services;

use App\Models\Estudiante;
use App\Models\Examen;
use Illuminate\Support\Facades\DB;
use BadMethodCallException;

/**
 * Servicio de comprobación de habilitación de estudiantes para exámenes (VER-02 / Tarea S3-VER02-01).
 *
 * Núcleo de VER-02:
 * Decide de manera pura e idempotente si un estudiante identificado puede rendir una sesión de examen específica.
 * Este servicio NO genera efectos secundarios ni escribe en la base de datos (inmutabilidad estricta).
 * El registro del intento de ingreso a un examen no correspondiente pertenece a VER-03 (Sprint 4);
 * aquí únicamente se resuelve y devuelve el estado de habilitación.
 *
 * Reglas de negocio evaluadas:
 * -------------------------------------------------------------------------
 * 1. Regla 'HABILITADO':
 *    Existe un registro en la tabla pivote (habilitacion / estudiante_examen) para el par
 *    (id_estudiante, id_examen) con estado 'HABILITADO' (habilitado = true).
 *    El estudiante cumple los requisitos y tiene permiso para rendir la prueba.
 *
 * 2. Regla 'NO_HABILITADO':
 *    Existe un registro en la tabla pivote para el par (id_estudiante, id_examen) con estado
 *    'NO_HABILITADO' (habilitado = false). Se retorna además el motivo por el cual no puede rendir.
 *
 * 3. Regla 'NO_ASOCIADO':
 *    No existe fila en la tabla pivote para ese examen (el estudiante no está registrado/asociado
 *    a la sesión). No se crea ningún registro en la base de datos y se retorna 'NO_ASOCIADO'.
 * -------------------------------------------------------------------------
 *
 * Consumidores del servicio:
 * - App\Http\Controllers\Api\VerificacionController (Endpoints /api/verificar/qr y /api/verificar/documento en VER-01 y VER-02).
 * - Controladores y servicios de registro de intentos e ingresos (VER-03 / Sprint 4).
 * - Procesos batch o de contingencia para auditoría y validación de listas de examen.
 */
class HabilitacionService
{
    /**
     * Comprueba el estado de habilitación de un estudiante para un examen en una sola consulta.
     * Utiliza el índice único de la tabla pivote (uq_habilitacion_estudiante_examen: id_estudiante, id_examen).
     *
     * Puede invocarse tanto estáticamente (HabilitacionService::comprobar) como a través
     * de una instancia inyectada ($this->habilitacionService->comprobar).
     *
     * @param int|string $examenId Identificador de la sesión de examen.
     * @param int|string $estudianteId Identificador del estudiante.
     * @return object Objeto con:
     *                - estado (string): 'HABILITADO' | 'NO_HABILITADO' | 'NO_ASOCIADO'
     *                - habilitado (bool): true solo si estado === 'HABILITADO'
     *                - motivo (string|null): Detalle del motivo o null si habilitado
     *                - id_habilitacion (int|null): Identificador del registro pivote si existe
     *                - id_examen (int): ID del examen evaluado
     *                - id_estudiante (int): ID del estudiante evaluado
     */
    public static function comprobar(int|string $examenId, int|string $estudianteId): object
    {
        $idExamen = (int) $examenId;
        $idEstudiante = (int) $estudianteId;

        // Resolución en 1 sola consulta SQL utilizando el índice único (id_estudiante, id_examen)
        $registro = DB::table('habilitacion')
            ->where('id_estudiante', $idEstudiante)
            ->where('id_examen', $idExamen)
            ->first();

        // Regla 3: NO_ASOCIADO (sin fila para ese examen)
        if (!$registro) {
            return (object) [
                'estado'          => 'NO_ASOCIADO',
                'habilitado'      => false,
                'motivo'          => 'El estudiante no está registrado en este examen.',
                'id_habilitacion' => null,
                'id_examen'       => $idExamen,
                'id_estudiante'   => $idEstudiante,
            ];
        }

        $estadoRaw = strtoupper(trim((string) ($registro->estado ?? '')));
        $esHabilitado = ($estadoRaw === 'HABILITADO' || (isset($registro->habilitado) && (bool) $registro->habilitado));

        // Regla 1: HABILITADO (fila presente con habilitado = true / estado = 'HABILITADO')
        if ($esHabilitado) {
            return (object) [
                'estado'          => 'HABILITADO',
                'habilitado'      => true,
                'motivo'          => null,
                'id_habilitacion' => (int) $registro->id_habilitacion,
                'id_examen'       => $idExamen,
                'id_estudiante'   => $idEstudiante,
            ];
        }

        // Regla 2: NO_HABILITADO (fila presente con habilitado = false / estado = 'NO_HABILITADO')
        $motivo = $registro->motivo_detalle
            ?? $registro->motivo
            ?? 'Inhabilitado para rendir este examen.';

        return (object) [
            'estado'          => 'NO_HABILITADO',
            'habilitado'      => false,
            'motivo'          => $motivo,
            'id_habilitacion' => (int) $registro->id_habilitacion,
            'id_examen'       => $idExamen,
            'id_estudiante'   => $idEstudiante,
        ];
    }

    /**
     * Método heredado para retrocompatibilidad con controladores existentes.
     *
     * @deprecated Utilizar el método comprobar(examenId, estudianteId) inyectado o estático.
     * @return array{estado: string, habilitado: bool, motivo: ?string, id_habilitacion: ?int}
     */
    public static function verificarHabilitacion(Estudiante $estudiante, Examen $examen): array
    {
        $res = static::comprobar($examen->id_examen, $estudiante->id_estudiante);

        return [
            'estado'          => $res->estado,
            'habilitado'      => $res->habilitado,
            'motivo'          => $res->motivo,
            'id_habilitacion' => $res->id_habilitacion,
        ];
    }
}
