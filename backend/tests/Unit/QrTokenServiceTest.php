<?php

namespace Tests\Unit;

use App\Exceptions\Qr\InvalidSignatureException;
use App\Exceptions\Qr\TokenMalformedException;
use App\Exceptions\Qr\UnsupportedVersionException;
use App\Models\Estudiante;
use App\Services\QrTokenService;
use Tests\TestCase;

class QrTokenServiceTest extends TestCase
{
    private QrTokenService $service;
    private Estudiante $estudiante;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new QrTokenService('test-secret-key-for-qr-tokens-32bytes');

        $this->estudiante = new Estudiante([
            'ci'                   => '12345678',
            'nombre'               => 'Carlos',
            'apellido'             => 'Pérez Gomez',
            'codigo_universitario' => '202300001',
            'correo'               => 'carlos.perez@umss.edu.bo',
            'estado'               => 'ACTIVO',
        ]);
        $this->estudiante->id_estudiante = 42;
    }

    /**
     * 1. Token válido: Genera un token para un estudiante y verifica que validar($token)
     * devuelva exactamente su id_estudiante.
     */
    public function test_token_valido_devuelve_id_estudiante(): void
    {
        $token = $this->service->generar($this->estudiante);

        $this->assertNotEmpty($token);
        $this->assertStringContainsString('.', $token);

        $idEstudiante = $this->service->validar($token);

        $this->assertSame(42, $idEstudiante);
    }

    /**
     * 2. Privacidad del token: Decodifica la parte del payload y comprueba que NO
     * contenga el CI, ni el nombre, ni el código SIS en claro (solo "v" y "sid").
     */
    public function test_privacidad_payload_no_contiene_datos_personales(): void
    {
        $token = $this->service->generar($this->estudiante);
        [$payloadB64] = explode('.', $token);

        $payloadJson = QrTokenService::base64UrlDecode($payloadB64);
        $this->assertNotFalse($payloadJson);

        $payload = json_decode($payloadJson, true);

        // Solo debe contener "v" y "sid"
        $this->assertArrayHasKey('v', $payload);
        $this->assertArrayHasKey('sid', $payload);
        $this->assertCount(2, $payload);
        $this->assertSame(1, $payload['v']);
        $this->assertSame(42, $payload['sid']);

        // Verificación negativa: sin datos personales en el payload
        $this->assertArrayNotHasKey('ci', $payload);
        $this->assertArrayNotHasKey('nombre', $payload);
        $this->assertArrayNotHasKey('apellido', $payload);
        $this->assertArrayNotHasKey('codigo_universitario', $payload);
        $this->assertArrayNotHasKey('correo', $payload);

        $this->assertStringNotContainsString('12345678', $payloadJson);
        $this->assertStringNotContainsString('Carlos', $payloadJson);
        $this->assertStringNotContainsString('202300001', $payloadJson);
    }

    /**
     * 3. Token alterado: Modifica un carácter del payload o de la firma y verifica que lance InvalidSignatureException.
     */
    public function test_token_con_payload_alterado_lanza_invalid_signature_exception(): void
    {
        $token = $this->service->generar($this->estudiante);
        [$payloadB64, $signatureB64] = explode('.', $token);

        // Alterar payload (ej. cambiando a sid=99)
        $tamperedJson = json_encode(['v' => 1, 'sid' => 99]);
        $tamperedPayloadB64 = QrTokenService::base64UrlEncode($tamperedJson);
        $tamperedToken = $tamperedPayloadB64 . '.' . $signatureB64;

        $this->expectException(InvalidSignatureException::class);
        $this->service->validar($tamperedToken);
    }

    public function test_token_con_firma_alterada_lanza_invalid_signature_exception(): void
    {
        $token = $this->service->generar($this->estudiante);
        [$payloadB64, $signatureB64] = explode('.', $token);

        // Alterar el último carácter de la firma
        $tamperedSignature = substr($signatureB64, 0, -1) . ($signatureB64[-1] === 'A' ? 'B' : 'A');
        $tamperedToken = $payloadB64 . '.' . $tamperedSignature;

        $this->expectException(InvalidSignatureException::class);
        $this->service->validar($tamperedToken);
    }

    /**
     * 4. Versión desconocida: Construye un payload firmado válidamente pero con {"v":2,"sid":10}
     * y verifica que lance UnsupportedVersionException.
     */
    public function test_version_desconocida_con_firma_valida_lanza_unsupported_version_exception(): void
    {
        // Generar un token con v=2 válidamente firmado por el mismo servicio
        $tokenV2 = $this->service->generarToken(10, 2);

        $this->expectException(UnsupportedVersionException::class);
        $this->service->validar($tokenV2);
    }

    /**
     * 5. Token vacío o malformado: Prueba con "", "abc", "sin.base64.valido" y verifica que lance TokenMalformedException.
     */
    public function test_token_vacio_lanza_token_malformed_exception(): void
    {
        $this->expectException(TokenMalformedException::class);
        $this->service->validar('');
    }

    public function test_token_sin_punto_lanza_token_malformed_exception(): void
    {
        $this->expectException(TokenMalformedException::class);
        $this->service->validar('abcde12345');
    }

    public function test_token_con_multiples_puntos_lanza_token_malformed_exception(): void
    {
        $this->expectException(TokenMalformedException::class);
        $this->service->validar('parte1.parte2.parte3');
    }

    public function test_token_con_segmento_vacio_lanza_token_malformed_exception(): void
    {
        $this->expectException(TokenMalformedException::class);
        $this->service->validar('.firma');
    }

    public function test_token_con_payload_no_json_lanza_token_malformed_exception(): void
    {
        $notJsonB64 = QrTokenService::base64UrlEncode('esto-no-es-json');
        $signatureRaw = hash_hmac('sha256', $notJsonB64, $this->service->getSecretKey(), true);
        $signatureB64 = QrTokenService::base64UrlEncode($signatureRaw);
        $token = $notJsonB64 . '.' . $signatureB64;

        $this->expectException(TokenMalformedException::class);
        $this->service->validar($token);
    }

    /**
     * 6. Renderizado de código QR en formato SVG.
     */
    public function test_generar_svg_retorna_xml_svg_valido(): void
    {
        $svg = $this->service->generarSvg($this->estudiante, 250);

        $this->assertNotEmpty($svg);
        $this->assertStringContainsString('<svg', $svg);
        $this->assertStringContainsString('</svg>', $svg);
    }
}
