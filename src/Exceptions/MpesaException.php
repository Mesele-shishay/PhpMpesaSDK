<?php

namespace MesaSDK\PhpMpesa\Exceptions;

class MpesaException extends \Exception
{
    private ?array $response;

    public function __construct(
        string $message = "",
        ?string $code = null,
        ?array $response = null,
        int $httpCode = 0,
        ?\Throwable $previous = null
    ) {
        $this->response = $response;
        parent::__construct($message, $httpCode, $previous);
    }

    /**
     * Create an exception instance from an M-Pesa API response
     * 
     * @param array $response The API response
     * @return self
     */
    public static function fromResponse(array $response): self
    {
        $message = $response['ResponseDescription'] ?? $response['errorMessage'] ?? 'Unknown error';
        $code = $response['ResponseCode'] ?? $response['errorCode'] ?? null;
        
        return new self($message, $code, $response);
    }

    /**
     * Get the raw API response that caused this exception
     * 
     * @return array|null
     */
    public function getResponse(): ?array
    {
        return $this->response;
    }
} 