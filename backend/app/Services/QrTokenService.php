<?php

namespace App\Services;

use App\Models\Estudiante;
use InvalidArgumentException;

class QrTokenService
{
    /**
     * Genera un token QR firmado para un estudiante.
     * Formato: base64(json_payload).hmac_sha256
     */
    public static function generarToken(Estudiante $estudiante, ?int $timestamp = null): string
    {
        $payload = [
            'id_estudiante'        => $estudiante->id_estudiante,
            'ci'                   => (string) $estudiante->ci,
            'codigo_universitario' => (string) $estudiante->codigo_universitario,
            'timestamp'            => $timestamp ?? time(),
        ];

        $encodedPayload = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');
        $secretKey = config('app.key') ?: 'clave-secreta-predeterminada-32ch';
        $signature = hash_hmac('sha256', $encodedPayload, $secretKey);

        return "{$encodedPayload}.{$signature}";
    }

    /**
     * Valida un token QR verificando estructura, formato y firma criptográfica.
     *
     * @return array{valido: bool, error: ?string, datos: ?array}
     */
    public static function validarToken(string $token): array
    {
        $partes = explode('.', trim($token));

        if (count($partes) !== 2) {
            return [
                'valido' => false,
                'error'  => 'FORMATO_INVALIDO',
                'datos'  => null,
            ];
        }

        [$encodedPayload, $firmaRecibida] = $partes;

        if (empty($encodedPayload) || empty($firmaRecibida)) {
            return [
                'valido' => false,
                'error'  => 'FORMATO_INVALIDO',
                'datos'  => null,
            ];
        }

        $secretKey = config('app.key') ?: 'clave-secreta-predeterminada-32ch';
        $firmaEsperada = hash_hmac('sha256', $encodedPayload, $secretKey);

        if (!hash_equals($firmaEsperada, $firmaRecibida)) {
            return [
                'valido' => false,
                'error'  => 'QR_ALTERADO',
                'datos'  => null,
            ];
        }

        $decodedJson = base64_decode(strtr($encodedPayload, '-_', '+/'), true);
        if ($decodedJson === false) {
            return [
                'valido' => false,
                'error'  => 'FORMATO_INVALIDO',
                'datos'  => null,
            ];
        }

        $datos = json_decode($decodedJson, true);
        if (!is_array($datos) || !isset($datos['id_estudiante'])) {
            return [
                'valido' => false,
                'error'  => 'FORMATO_INVALIDO',
                'datos'  => null,
            ];
        }

        return [
            'valido' => true,
            'error'  => null,
            'datos'  => $datos,
        ];
    }
}
