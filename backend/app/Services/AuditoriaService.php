<?php

namespace App\Services;

use App\Models\Auditoria;
use Illuminate\Support\Facades\Log;

class AuditoriaService
{
    /**
     * Registrar una acción en la tabla de auditoría.
     *
     * @param int|string|null $idUsuario ID del usuario que ejecuta la acción
     * @param string $accion Acción realizada (CREACION, MODIFICACION, ELIMINACION, etc.)
     * @param string $entidad Nombre de la entidad (EXAMEN, ESTUDIANTE, etc.)
     * @param int|string|null $idRegistro ID del registro afectado
     * @param string $resultado Resultado de la operación (EXITO, FALLO, etc.)
     * @param string|null $descripcion Detalles descriptivos de la acción
     * @return Auditoria|null
     */
    public static function registrar(
        int|string|null $idUsuario,
        string $accion,
        string $entidad,
        int|string|null $idRegistro,
        string $resultado = 'EXITO',
        ?string $descripcion = null
    ): ?Auditoria {
        try {
            if (!$idUsuario) {
                return null;
            }

            return Auditoria::create([
                'id_usuario'  => $idUsuario,
                'accion'      => strtoupper($accion),
                'entidad'     => strtoupper($entidad),
                'id_registro' => $idRegistro ? (int) $idRegistro : null,
                'fecha_hora'  => now(),
                'resultado'   => strtoupper($resultado),
                'descripcion' => $descripcion,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error al registrar auditoría: ' . $e->getMessage(), [
                'id_usuario' => $idUsuario,
                'accion'     => $accion,
                'entidad'    => $entidad,
                'id_reg'     => $idRegistro,
            ]);
            return null;
        }
    }
}
