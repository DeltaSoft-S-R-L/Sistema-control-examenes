<?php

namespace App\Services;

use App\Exceptions\Qr\InvalidSignatureException;
use App\Exceptions\Qr\TokenMalformedException;
use App\Exceptions\Qr\UnsupportedVersionException;
use App\Models\Estudiante;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use RuntimeException;

class QrTokenService
{
    protected ?string $secretKey;

    public function __construct(?string $secretKey = null)
    {
        $this->secretKey = $secretKey;
    }

    /**
     * Obtiene la clave secreta de la aplicación decodificando el prefijo base64: si existe.
     */
    public function getSecretKey(): string
    {
        if ($this->secretKey !== null && $this->secretKey !== '') {
            return $this->secretKey;
        }

        $key = (string) config('app.key');
        if (empty($key)) {
            throw new RuntimeException('No se ha configurado la clave de cifrado de la aplicación (APP_KEY).');
        }

        if (str_starts_with($key, 'base64:')) {
            $decoded = base64_decode(substr($key, 7), true);
            if ($decoded !== false) {
                return $decoded;
            }
        }

        return $key;
    }

    /**
     * Codifica datos en formato Base64URL (RFC 4648 §5).
     */
    public static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Decodifica un string en formato Base64URL.
     */
    public static function base64UrlDecode(string $data): string|false
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(strtr($data, '-_', '+/'), true);
    }

    /**
     * Genera un token firmado para el estudiante dado.
     * Estructura: <payload_base64url>.<firma_base64url>
     * Payload JSON: {"v":1,"sid":<id_estudiante>}
     */
    public function generar(Estudiante $estudiante): string
    {
        return $this->generarToken((int) $estudiante->id_estudiante, 1);
    }

    /**
     * Genera un token con parámetros específicos (usado internamente y en pruebas).
     */
    public function generarToken(int $idEstudiante, int $version = 1): string
    {
        $payload = [
            'v'   => $version,
            'sid' => $idEstudiante,
        ];

        $payloadJson = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $payloadB64 = self::base64UrlEncode($payloadJson);

        $firmaRaw = hash_hmac('sha256', $payloadB64, $this->getSecretKey(), true);
        $firmaB64 = self::base64UrlEncode($firmaRaw);

        return $payloadB64 . '.' . $firmaB64;
    }

    /**
     * Valida el token QR y retorna el id_estudiante.
     *
     * @throws TokenMalformedException Si el token está vacío, no tiene 2 segmentos, o el payload no es JSON válido.
     * @throws InvalidSignatureException Si la firma HMAC no coincide o fue alterada.
     * @throws UnsupportedVersionException Si el campo "v" no es exactamente 1.
     */
    public function validar(string $token): int
    {
        $token = trim($token);
        if ($token === '') {
            throw new TokenMalformedException('El token QR no puede estar vacío.');
        }

        $partes = explode('.', $token);
        if (count($partes) !== 2) {
            throw new TokenMalformedException('El token QR debe contener exactamente dos segmentos separados por un punto.');
        }

        [$payloadB64, $firmaB64] = $partes;
        if ($payloadB64 === '' || $firmaB64 === '') {
            throw new TokenMalformedException('El token QR contiene segmentos vacíos.');
        }

        $payloadJson = self::base64UrlDecode($payloadB64);
        if ($payloadJson === false || $payloadJson === '') {
            throw new TokenMalformedException('El payload del token QR no es un Base64URL válido.');
        }

        $payload = json_decode($payloadJson, true);
        if (!is_array($payload) || json_last_error() !== JSON_ERROR_NONE) {
            throw new TokenMalformedException('El payload del token QR no contiene una estructura JSON válida.');
        }

        // 1. Verificación de firma criptográfica HMAC en tiempo constante
        $firmaEsperadaRaw = hash_hmac('sha256', $payloadB64, $this->getSecretKey(), true);
        $firmaEsperadaB64 = self::base64UrlEncode($firmaEsperadaRaw);

        if (!hash_equals($firmaEsperadaB64, $firmaB64)) {
            throw new InvalidSignatureException('La firma digital del token QR no coincide o ha sido adulterada.');
        }

        // 2. Validación de campos obligatorios
        if (!array_key_exists('v', $payload) || !array_key_exists('sid', $payload)) {
            throw new TokenMalformedException('El payload del token QR carece de los campos requeridos ("v", "sid").');
        }

        // 3. Validación de versión soportada
        if ((int) $payload['v'] !== 1) {
            throw new UnsupportedVersionException(
                sprintf('La versión %s del formato QR no está soportada. Solo se admite la versión 1.', $payload['v'])
            );
        }

        // 4. Validación del identificador de estudiante
        if (!is_numeric($payload['sid']) || (int) $payload['sid'] <= 0) {
            throw new TokenMalformedException('El identificador de estudiante "sid" es inválido.');
        }

        return (int) $payload['sid'];
    }

    /**
     * Genera el código QR en formato SVG conteniendo el token firmado del estudiante.
     */
    public function generarSvg(Estudiante $estudiante, int $size = 300): string
    {
        $token = $this->generar($estudiante);
        return $this->generarSvgPorToken($token, $size);
    }

    /**
     * Genera el código QR en formato SVG a partir de un token string arbitrario.
     */
    public function generarSvgPorToken(string $token, int $size = 300): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle($size),
            new SvgImageBackEnd()
        );

        $writer = new Writer($renderer);
        return $writer->writeString($token);
    }
}
