<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class ValidadorFotoEstudianteService
{
    public function validar(UploadedFile $archivo): array
    {
        $rechazo = static fn (string $motivo): array => [
            'aprobada' => false,
            'validacion_completa' => false,
            'motivos' => [$motivo],
        ];

        $rutaPython = base_path(
            'analizador_facial/.venv/Scripts/python.exe'
        );

        $rutaAnalizador = base_path(
            'analizador_facial/detectar_rostro.py'
        );

        $rutaFoto = $archivo->getRealPath();

        if (!is_file($rutaPython) || !is_file($rutaAnalizador)) {
            return $rechazo(
                'El servicio de validación facial no está disponible.'
            );
        }

        if (!$rutaFoto || !is_file($rutaFoto)) {
            return $rechazo(
                'No se pudo acceder a la fotografía.'
            );
        }

        try {
            $proceso = new Process([
                $rutaPython,
                $rutaAnalizador,
                $rutaFoto,
            ]);

            $proceso->setTimeout(30);
            $proceso->run();

            if (!$proceso->isSuccessful()) {
    Log::error('Falló el analizador facial de Python', [
        'codigo_salida' => $proceso->getExitCode(),
        'salida_error' => $proceso->getErrorOutput(),
        'salida_python' => $proceso->getOutput(),
    ]);

    return $rechazo(
        'No fue posible analizar la fotografía.'
    );
}

            $resultado = json_decode(
                $proceso->getOutput(),
                true
            );

            if (!is_array($resultado)) {
                return $rechazo(
                    'El analizador facial devolvió una respuesta inválida.'
                );
            }

            $aprobada = ($resultado['aprobada'] ?? null) === true;
            $completa = ($resultado['validacion_completa'] ?? null) === true;

            if (!$aprobada || !$completa) {
                $resultado['aprobada'] = false;

                if (!isset($resultado['motivos']) ||
                    !is_array($resultado['motivos'])) {
                    $resultado['motivos'] = [];
                }

                if (!$completa) {
                    $resultado['motivos'][] =
                        'La validación facial todavía no está completa.';
                }
            }

            return $resultado;

        } catch (\Throwable $e) {
            return $rechazo(
                'Ocurrió un error durante la validación facial.'
            );
        }
    }
}
