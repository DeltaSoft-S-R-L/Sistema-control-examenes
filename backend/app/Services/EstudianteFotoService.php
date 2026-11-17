<?php

namespace App\Services;

use Cloudinary\Cloudinary;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use RuntimeException;

class EstudianteFotoService
{
    private ?Cloudinary $cloudinary = null;

    public function __construct()
    {
        $cloudName = config('services.cloudinary.cloud_name');
        $apiKey = config('services.cloudinary.api_key');
        $apiSecret = config('services.cloudinary.api_secret');

        // The production WebTIS vendor tree may not include optional packages
        // unless Composer has been run there. Fall back to private local storage
        // instead of throwing when Cloudinary's SDK is unavailable.
        if ($cloudName && $apiKey && $apiSecret && class_exists(Cloudinary::class)) {
            $this->cloudinary = new Cloudinary([
                'cloud' => [
                    'cloud_name' => $cloudName,
                    'api_key' => $apiKey,
                    'api_secret' => $apiSecret,
                ],
            ]);
        }
    }

    /**
     * Devuelve el cliente configurado de Cloudinary.
     */
    public function cliente(): ?Cloudinary
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
        if ($this->cloudinary) {
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

        $nombre = 'estudiante_' . $idEstudiante . '_' . bin2hex(random_bytes(12)) . '.jpg';
        $ruta = Storage::disk('local')->putFileAs('estudiantes/fotos', $archivo, $nombre);
        if (!$ruta) {
            throw new RuntimeException('No se pudo guardar la fotografía localmente.');
        }

        return ['public_id' => $ruta, 'type' => 'local'];
    }

    /**
     * Elimina una fotografía autenticada de Cloudinary.
     *
     * Se utiliza para compensar una subida cuando falla
     * el registro correspondiente en PostgreSQL.
     */
    public function eliminarFoto(string $publicId, string $type = 'authenticated'): void
    {
        if ($type === 'local') {
            Storage::disk('local')->delete($publicId);
            return;
        }

        if (!$this->cloudinary) {
            throw new RuntimeException('Cloudinary no está configurado para eliminar esta fotografía.');
        }

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
public function obtenerUrlTemporal(string $publicId, string $type = 'authenticated', ?int $idEstudiante = null): string
{
    if ($type === 'local') {
        if (!$idEstudiante) {
            throw new RuntimeException('Se requiere el estudiante para generar el enlace de la fotografía.');
        }

        return URL::temporarySignedRoute(
            'estudiante.foto.archivo',
            now()->addMinutes(5),
            ['id_estudiante' => $idEstudiante]
        );
    }

    if (!$this->cloudinary) {
        throw new RuntimeException('Cloudinary no está configurado.');
    }

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
