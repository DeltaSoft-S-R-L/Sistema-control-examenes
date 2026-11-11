<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use RuntimeException;

class ValidadorFotoEstudianteService
{
    /**
     * Analiza la iluminación y la nitidez de una fotografía.
     *
     * Estas comprobaciones son aproximaciones técnicas.
     * La detección facial se implementará por separado.
     */
    public function validar(UploadedFile $archivo): array
    {
        if (!extension_loaded('gd')) {
            throw new RuntimeException(
                'La extensión GD no está habilitada.'
            );
        }

        $ruta = $archivo->getRealPath();

        if (!$ruta) {
            throw new RuntimeException(
                'No se pudo acceder a la fotografía.'
            );
        }

        $contenido = file_get_contents($ruta);

        if ($contenido === false) {
            throw new RuntimeException(
                'No se pudo leer la fotografía.'
            );
        }

        $imagen = @imagecreatefromstring($contenido);

        if ($imagen === false) {
            return [
                'aprobada' => false,
                'motivos' => [
                    'No se pudo procesar la imagen.',
                ],
            ];
        }

        try {
            $ancho = imagesx($imagen);
            $alto = imagesy($imagen);

            // Muestreo para evitar procesar millones de píxeles.
            $paso = max(1, (int) ceil(max($ancho, $alto) / 200));

            $sumaLuz = 0.0;
            $cantidad = 0;
            $sumaDiferencias = 0.0;
            $comparaciones = 0;

            for ($y = 0; $y < $alto; $y += $paso) {
                for ($x = 0; $x < $ancho; $x += $paso) {
                    $actual = $this->luminosidad(
                        imagecolorat($imagen, $x, $y)
                    );

                    $sumaLuz += $actual;
                    $cantidad++;

                    if ($x + $paso < $ancho) {
                        $derecha = $this->luminosidad(
                            imagecolorat($imagen, $x + $paso, $y)
                        );

                        $sumaDiferencias += abs($actual - $derecha);
                        $comparaciones++;
                    }

                    if ($y + $paso < $alto) {
                        $abajo = $this->luminosidad(
                            imagecolorat($imagen, $x, $y + $paso)
                        );

                        $sumaDiferencias += abs($actual - $abajo);
                        $comparaciones++;
                    }
                }
            }

            $iluminacion = $cantidad > 0
                ? $sumaLuz / $cantidad
                : 0;

            $detalle = $comparaciones > 0
                ? $sumaDiferencias / $comparaciones
                : 0;

            $motivos = [];

            // Umbrales iniciales sujetos a pruebas.
            if ($iluminacion < 55) {
                $motivos[] = 'La fotografía está demasiado oscura.';
            }

            if ($iluminacion > 220) {
                $motivos[] = 'La fotografía tiene demasiada iluminación.';
            }

            if ($detalle < 8) {
                $motivos[] = 'La fotografía podría estar borrosa o tener poco detalle.';
            }

            return [
                'aprobada' => count($motivos) === 0,
                'motivos' => $motivos,
                'metricas' => [
                    'iluminacion' => round($iluminacion, 2),
                    'detalle' => round($detalle, 2),
                ],
            ];
        } finally {
            imagedestroy($imagen);
        }
    }

    /**
     * Calcula la luminosidad aproximada de un píxel.
     */
    private function luminosidad(int $color): float
    {
        $rojo = ($color >> 16) & 0xFF;
        $verde = ($color >> 8) & 0xFF;
        $azul = $color & 0xFF;

        return (0.2126 * $rojo)
            + (0.7152 * $verde)
            + (0.0722 * $azul);
    }
}
