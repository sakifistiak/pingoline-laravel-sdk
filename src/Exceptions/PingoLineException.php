<?php

namespace PingoLine\Laravel\Exceptions;

use Exception;

class PingoLineException extends Exception
{
    protected ?string $errorCode;
    protected ?array $errorData;

    public function __construct(string $message = '', int $code = 0, ?string $errorCode = null, ?array $errorData = null, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->errorCode = $errorCode;
        $this->errorData = $errorData;
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    public function getErrorData(): ?array
    {
        return $this->errorData;
    }
}
