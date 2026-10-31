<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuditService
{
    /**
     * Enmascarar un valor sensible mostrando únicamente los últimos 3 caracteres.
     * Si tiene menos de 3 caracteres o está vacío, retorna '***'.
     */
    public static function enmascararValor(?string $valor): string
    {
        if ($valor === null) {
            return '***';
        }

        $limpio = trim($valor);
        $longitud = mb_strlen($limpio);

        if ($longitud < 3) {
            return '***';
        }

        $ultimos3 = mb_substr($limpio, -3);

        return '***' . $ultimos3;
    }

    /**
     * Registrar un evento de verificación de identidad en la tabla audit_logs (TEC-02 / S3-VER01-05).
     *
     * @param array{
     *     metodo: string,
     *     examen_id: int|string|null,
     *     estudiante_id?: int|string|null,
     *     resultado: string,
     *     valor_enmascarado?: string|null,
     *     id_usuario?: int|string|null,
     *     ip_address?: string|null,
     *     detalles?: array
     * } $datos
     */
    public function registrarVerificacion(array $datos): ?AuditLog
    {
        return self::registrarVerificacionEstatico($datos);
    }

    /**
     * Implementación estática del registro de verificación para facilitar llamadas estáticas o desacopladas.
     */
    public static function registrarVerificacionEstatico(array $datos): ?AuditLog
    {
        try {
            $idUsuario = $datos['id_usuario']
                ?? Auth::guard('sanctum')->id()
                ?? request()->user()?->id_usuario
                ?? request()->user()?->id
                ?? Auth::id();

            $estudianteId = isset($datos['estudiante_id']) && $datos['estudiante_id'] !== null
                ? (int) $datos['estudiante_id']
                : null;

            $examenId = isset($datos['examen_id']) && $datos['examen_id'] !== null && is_numeric($datos['examen_id'])
                ? (int) $datos['examen_id']
                : null;

            $resultado = $datos['resultado'] ?? 'EXITOSO';
            $metodo = strtoupper((string) ($datos['metodo'] ?? 'QR'));

            $informacion = [
                'metodo'        => $metodo,
                'examen_id'     => $examenId,
                'estudiante_id' => $estudianteId,
                'resultado'     => $resultado,
                'ip_address'    => $datos['ip_address'] ?? request()->ip(),
            ];

            if (!empty($datos['valor_enmascarado'])) {
                $informacion['valor_enmascarado'] = $datos['valor_enmascarado'];
            }

            if (!empty($datos['detalles']) && is_array($datos['detalles'])) {
                $informacion['detalles'] = $datos['detalles'];
            }

            return AuditLog::create([
                'id_usuario'  => $idUsuario ? (int) $idUsuario : null,
                'accion'      => 'VERIFICACION_IDENTIDAD',
                'entidad'     => 'estudiante',
                'id_registro' => $estudianteId ? (string) $estudianteId : null,
                'fecha_hora'  => now(),
                'informacion' => $informacion,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error al registrar auditoría de verificación: ' . $e->getMessage(), [
                'datos'     => $datos,
                'exception' => $e,
            ]);

            return null;
        }
    }

    /**
     * Registro genérico en audit_logs (TEC-02).
     */
    public static function registrar(
        string $accion,
        string $entidad = 'sistema',
        string|int|null $idRegistro = null,
        array $informacion = [],
        int|string|null $idUsuario = null
    ): ?AuditLog {
        try {
            $idUsuarioFinal = $idUsuario
                ?? Auth::guard('sanctum')->id()
                ?? request()->user()?->id_usuario
                ?? request()->user()?->id
                ?? Auth::id();

            return AuditLog::create([
                'id_usuario'  => $idUsuarioFinal ? (int) $idUsuarioFinal : null,
                'accion'      => $accion,
                'entidad'     => $entidad,
                'id_registro' => $idRegistro ? (string) $idRegistro : null,
                'fecha_hora'  => now(),
                'informacion' => $informacion,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error al registrar auditoría: ' . $e->getMessage(), [
                'accion'    => $accion,
                'entidad'   => $entidad,
                'exception' => $e,
            ]);

            return null;
        }
    }
}
