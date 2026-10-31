<?php

namespace App\Exceptions\Qr;

use Exception;

class UnsupportedVersionException extends Exception
{
    public function __construct(string $message = 'La versión del formato del token QR no está soportada.', int $code = 422, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
