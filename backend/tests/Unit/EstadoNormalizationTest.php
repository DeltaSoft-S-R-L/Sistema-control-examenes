<?php

namespace Tests\Unit;

use App\Models\Estudiante;
use App\Models\Usuario;
use PHPUnit\Framework\TestCase;

class EstadoNormalizationTest extends TestCase
{
    public function test_los_modelos_normalizan_estado_a_mayusculas(): void
    {
        $usuario = new Usuario(['estado' => ' revocado ']);
        $estudiante = new Estudiante(['estado' => ' inactivo ']);

        $this->assertSame('REVOCADO', $usuario->estado);
        $this->assertSame('INACTIVO', $estudiante->estado);
    }
}
