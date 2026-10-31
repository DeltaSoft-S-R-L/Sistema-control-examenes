<?php

namespace App\Exceptions\Qr;

use Exception;

class InvalidSignatureException extends Exception
{
    public function __construct(string $message = 'La firma digital del token QR no coincide o ha sido adulterada.', int $code = 422, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
