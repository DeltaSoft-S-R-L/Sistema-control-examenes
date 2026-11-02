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
    private QrTokenService $qrService;
    private Estudiante $estudiante;

    protected function setUp(): void
    {
        parent::setUp();
        $this->qrService = new QrTokenService('test-secret-key-32-characters-ok!');

        $this->estudiante = new Estudiante();
        $this->estudiante->id_estudiante = 42;
        $this->estudiante->ci = '1234567';
        $this->estudiante->codigo_universitario = '202300001';
        $this->estudiante->nombre = 'Estudiante';
        $this->estudiante->apellido = 'Prueba';
    }

    private function crearEstudiantePrueba(): Estudiante
    {
        return $this->estudiante;
    }

    // ─────────────────────────────────────────────────────────────
    // Tests de origin/dev
    // ─────────────────────────────────────────────────────────────

    public function test_genera_y_valida_token_qr_correctamente(): void
    {
        $estudiante = $this->crearEstudiantePrueba();
        $token = QrTokenService::generarToken($estudiante);

        $this->assertNotEmpty($token);
        $this->assertStringContainsString('.', $token);

        $resultado = QrTokenService::validarToken($token);

        $this->assertTrue($resultado['valido']);
        $this->assertNull($resultado['error']);
        $this->assertNotNull($resultado['datos']);
        $this->assertEquals(42, $resultado['datos']['id_estudiante']);
        $this->assertEquals('1234567', $resultado['datos']['ci']);
        $this->assertEquals('202300001', $resultado['datos']['codigo_universitario']);
    }

    public function test_rechaza_qr_alterado_por_firma_invalida(): void
    {
        $estudiante = $this->crearEstudiantePrueba();
        $token = QrTokenService::generarToken($estudiante);

        [$payload, $firma] = explode('.', $token);

        // Alteramos la firma
        $firmaAlterada = substr($firma, 0, -2) . 'ab';
        $tokenAlteradoFirma = "{$payload}.{$firmaAlterada}";

        $resFirma = QrTokenService::validarToken($tokenAlteradoFirma);
        $this->assertFalse($resFirma['valido']);
        $this->assertEquals('QR_ALTERADO', $resFirma['error']);

        // Alteramos el payload
        $payloadAlterado = substr($payload, 0, -2) . 'Zz';
        $tokenAlteradoPayload = "{$payloadAlterado}.{$firma}";

        $resPayload = QrTokenService::validarToken($tokenAlteradoPayload);
        $this->assertFalse($resPayload['valido']);
        $this->assertEquals('QR_ALTERADO', $resPayload['error']);
    }

    public function test_rechaza_token_con_formato_invalido(): void
    {
        // Sin separador punto
        $resSinPunto = QrTokenService::validarToken('tokeninvalidsinpunto');
        $this->assertFalse($resSinPunto['valido']);
        $this->assertEquals('FORMATO_INVALIDO', $resSinPunto['error']);

        // Con más de un punto
        $resVariosPuntos = QrTokenService::validarToken('parte1.parte2.parte3');
        $this->assertFalse($resVariosPuntos['valido']);
        $this->assertEquals('FORMATO_INVALIDO', $resVariosPuntos['error']);

        // Partes vacías
        $resVacio = QrTokenService::validarToken('.firma');
        $this->assertFalse($resVacio['valido']);
        $this->assertEquals('FORMATO_INVALIDO', $resVacio['error']);

        // Cadena vacía
        $resVacioTotal = QrTokenService::validarToken('   ');
        $this->assertFalse($resVacioTotal['valido']);
        $this->assertEquals('FORMATO_INVALIDO', $resVacioTotal['error']);
    }

    public function test_rechaza_token_con_payload_no_json(): void
    {
        $fakePayload = rtrim(strtr(base64_encode('texto-plano-no-json'), '+/', '-_'), '=');
        $secretKey = config('app.key') ?: 'clave-secreta-predeterminada-32ch';
        $firma = hash_hmac('sha256', $fakePayload, $secretKey);
        $tokenCorrupto = "{$fakePayload}.{$firma}";

        $resultado = QrTokenService::validarToken($tokenCorrupto);
        $this->assertFalse($resultado['valido']);
        $this->assertEquals('FORMATO_INVALIDO', $resultado['error']);
    }

    // ─────────────────────────────────────────────────────────────
    // Tests de VER-01 (S3-VER01-01)
    // ─────────────────────────────────────────────────────────────

    public function test_token_valido_devuelve_id_estudiante(): void
    {
        $token = $this->qrService->generar($this->estudiante);

        $idEstudiante = $this->qrService->validar($token);

        $this->assertSame(42, $idEstudiante);
    }

    public function test_privacidad_payload_no_contiene_datos_personales(): void
    {
        $token = $this->qrService->generar($this->estudiante);
        [$payloadB64] = explode('.', $token);
        $payloadJson = QrTokenService::base64UrlDecode($payloadB64);
        $payload = json_decode($payloadJson, true);

        $this->assertEquals(['v', 'sid'], array_keys($payload));
        $this->assertSame(1, $payload['v']);
        $this->assertSame(42, $payload['sid']);
        $this->assertArrayNotHasKey('ci', $payload);
        $this->assertArrayNotHasKey('nombre', $payload);
    }

    public function test_token_con_payload_alterado_lanza_invalid_signature_exception(): void
    {
        $token = $this->qrService->generar($this->estudiante);
        [$payloadB64, $signatureB64] = explode('.', $token);

        $tamperedPayload = json_encode(['v' => 1, 'sid' => 99]);
        $tamperedPayloadB64 = QrTokenService::base64UrlEncode($tamperedPayload);
        $tamperedToken = $tamperedPayloadB64 . '.' . $signatureB64;

        $this->expectException(InvalidSignatureException::class);
        $this->qrService->validar($tamperedToken);
    }

    public function test_token_con_firma_alterada_lanza_invalid_signature_exception(): void
    {
        $token = $this->qrService->generar($this->estudiante);
        [$payloadB64, $signatureB64] = explode('.', $token);

        $alteredSignatureB64 = substr($signatureB64, 0, -2) . 'ZZ';
        $tamperedToken = $payloadB64 . '.' . $alteredSignatureB64;

        $this->expectException(InvalidSignatureException::class);
        $this->qrService->validar($tamperedToken);
    }

    public function test_version_desconocida_con_firma_valida_lanza_unsupported_version_exception(): void
    {
        $tokenV2 = $this->qrService->generarVersionado(42, 2);

        $this->expectException(UnsupportedVersionException::class);
        $this->qrService->validar($tokenV2);
    }

    public function test_token_vacio_lanza_token_malformed_exception(): void
    {
        $this->expectException(TokenMalformedException::class);
        $this->qrService->validar('');
    }

    public function test_token_sin_punto_lanza_token_malformed_exception(): void
    {
        $this->expectException(TokenMalformedException::class);
        $this->qrService->validar('tokeninvalidsinseparadordepunto');
    }

    public function test_token_con_multiples_puntos_lanza_token_malformed_exception(): void
    {
        $this->expectException(TokenMalformedException::class);
        $this->qrService->validar('a.b.c');
    }

    public function test_token_con_segmento_vacio_lanza_token_malformed_exception(): void
    {
        $this->expectException(TokenMalformedException::class);
        $this->qrService->validar('.signature');
    }

    public function test_token_con_payload_no_json_lanza_token_malformed_exception(): void
    {
        $notJsonB64 = QrTokenService::base64UrlEncode('cadena-no-json');
        $firma = hash_hmac('sha256', $notJsonB64, $this->qrService->getSecretKey(), true);
        $token = $notJsonB64 . '.' . QrTokenService::base64UrlEncode($firma);

        $this->expectException(TokenMalformedException::class);
        $this->qrService->validar($token);
    }

    public function test_generar_svg_retorna_xml_svg_valido(): void
    {
        $svg = $this->qrService->generarSvg($this->estudiante);

        $this->assertNotEmpty($svg);
        $this->assertStringContainsString('<svg', $svg);
        $this->assertStringContainsString('</svg>', $svg);
    }
}
