<?php

namespace App\Services;

use Cloudinary\Cloudinary;
use Illuminate\Http\UploadedFile;
use RuntimeException;

class EstudianteFotoService
{
    private Cloudinary $cloudinary;

    public function __construct()
    {
        $cloudName = config('services.cloudinary.cloud_name');
        $apiKey = config('services.cloudinary.api_key');
        $apiSecret = config('services.cloudinary.api_secret');

        if (!$cloudName || !$apiKey || !$apiSecret) {
            throw new RuntimeException(
                'La configuracion de Cloudinary esta incompleta.'
            );
        }

        $this->cloudinary = new Cloudinary([
            'cloud' => [
                'cloud_name' => $cloudName,
                'api_key' => $apiKey,
                'api_secret' => $apiSecret,
            ],
        ]);
    }

    /**
     * Devuelve el cliente configurado de Cloudinary.
     */
    public function cliente(): Cloudinary
    {
        return $this->cloudinary;
    }

    /**
     * Sube una fotografía de estudiante a Cloudinary
     * utilizando acceso autenticado.
     *
     * La autorización y validación deben realizarse
     * antes de invocar este método.
     */
    public function subirFoto(
        UploadedFile $archivo,
        int $idEstudiante
    ): array {
        $resultado = $this->cloudinary->uploadApi()->upload(
            $archivo->getRealPath(),
            [
                'folder' => 'control_examenes/estudiantes',
                'public_id' => 'estudiante_' . $idEstudiante . '_' . bin2hex(random_bytes(12)),
                'type' => 'authenticated',
                'resource_type' => 'image',
                'overwrite' => false,
                'unique_filename' => false,
            ]
        );

        return [
            'public_id' => $resultado['public_id'],
            'type' => $resultado['type'],
        ];
    }

    /**
     * Elimina una fotografía autenticada de Cloudinary.
     *
     * Se utiliza para compensar una subida cuando falla
     * el registro correspondiente en PostgreSQL.
     */
    public function eliminarFoto(string $publicId): void
    {
        $resultado = $this->cloudinary->uploadApi()->destroy(
            $publicId,
            [
                'resource_type' => 'image',
                'type' => 'authenticated',
                'invalidate' => true,
            ]
        );

        if (($resultado['result'] ?? null) !== 'ok') {
            throw new RuntimeException(
                'Cloudinary no confirmó la eliminación de la fotografía.'
            );
        }
    }
    /**
 * Genera una URL temporal para acceder a una fotografía autenticada.
 */
public function obtenerUrlTemporal(string $publicId): string
{
    return $this->cloudinary->uploadApi()->privateDownloadUrl(
        $publicId,
        'jpg',
        [
            'type' => 'authenticated',
            'expires_at' => time() + 300,
        ]
    );
}
}
