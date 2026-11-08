<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class EstudianteNoAsociadoException extends Exception
{
    protected int $statusCode;
    protected string $errorCode;

    public function __construct(
        string $message = 'El estudiante no pertenece a este examen.',
        int $statusCode = 422,
        string $errorCode = 'NO_ASOCIADO',
        ?Throwable $previous = null
    ) {
        $this->statusCode = $statusCode;
        $this->errorCode = $errorCode;
        parent::__construct($message, $statusCode, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * Convierte la excepción al array con el contrato estándar de errores de negocio.
     *
     * @return array{message: string, code: string, error: string}
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'code'    => $this->errorCode,
            'error'   => $this->errorCode,
        ];
    }

    /**
     * Renderiza la respuesta JSON ante una excepción no capturada en el pipeline de Laravel.
     */
    public function render(Request $request): JsonResponse
    {
        return response()->json($this->toArray(), $this->statusCode);
    }
}
