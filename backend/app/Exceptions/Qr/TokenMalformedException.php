<?php

namespace App\Exceptions\Qr;

use Exception;

class TokenMalformedException extends Exception
{
    public function __construct(string $message = 'El token QR es inválido o tiene un formato malformado.', int $code = 422, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
