<?php

namespace Tests\Unit;

use App\Models\Estudiante;
use App\Services\QrTokenService;
use Tests\TestCase;

class QrTokenServiceTest extends TestCase
{
    private function crearEstudiantePrueba(): Estudiante
    {
        $estudiante = new Estudiante();
        $estudiante->id_estudiante = 101;
        $estudiante->ci = '1234567';
        $estudiante->codigo_universitario = '202300001';
        $estudiante->nombre = 'Estudiante';
        $estudiante->apellido = 'Prueba';
        return $estudiante;
    }

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
        $this->assertEquals(101, $resultado['datos']['id_estudiante']);
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
}
