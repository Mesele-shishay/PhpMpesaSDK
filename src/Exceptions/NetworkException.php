<?php

namespace MesaSDK\PhpMpesa\Exceptions;

/**
 * Exception thrown when network-related errors occur
 */
class NetworkException extends BaseException
{
    /** @var int|null HTTP status code */
    private ?int $statusCode;

    /** @var string|null Request URL */
    private ?string $url;

    /**
     * NetworkException constructor
     *
     * @param string $message Error message
     * @param int|null $statusCode HTTP status code
     * @param string|null $url Request URL
     * @param array|null $context Additional context
     * @param int $code Error code
     * @param \Throwable|null $previous Previous exception
     */
    public function __construct(
        string $message,
        ?int $statusCode = null,
        ?string $url = null,
        ?array $context = null,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        $this->statusCode = $statusCode;
        $this->url = $url;
        parent::__construct($message, $context, $code, $previous);
    }

    /**
     * Get the HTTP status code
     *
     * @return int|null
     */
    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    /**
     * Get the request URL
     *
     * @return string|null
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * Create exception from a connection timeout
     *
     * @param string $url The URL that timed out
     * @param array|null $context Additional context
     * @return self
     */
    public static function connectionTimeout(string $url, ?array $context = null): self
    {
        return new self(
            "Connection timed out while connecting to $url",
            null,
            $url,
            $context
        );
    }
}