<?php

namespace MesaSDK\PhpMpesa\Exceptions;

/**
 * Exception thrown when authentication-related errors occur
 */
class AuthenticationException extends BaseException
{
    /** @var string|null The authentication error type */
    private ?string $errorType;

    /**
     * Authentication error types
     */
    public const ERROR_INVALID_CREDENTIALS = 'invalid_credentials';
    public const ERROR_EXPIRED_TOKEN = 'expired_token';
    public const ERROR_INVALID_TOKEN = 'invalid_token';
    public const ERROR_MISSING_CREDENTIALS = 'missing_credentials';

    /**
     * AuthenticationException constructor
     *
     * @param string $message Error message
     * @param string|null $errorType Authentication error type
     * @param array|null $context Additional context
     * @param int $code Error code
     * @param \Throwable|null $previous Previous exception
     */
    public function __construct(
        string $message,
        ?string $errorType = null,
        ?array $context = null,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        $this->errorType = $errorType;
        parent::__construct($message, $context, $code, $previous);
    }

    /**
     * Get the authentication error type
     *
     * @return string|null
     */
    public function getErrorType(): ?string
    {
        return $this->errorType;
    }

    /**
     * Create an invalid credentials exception
     *
     * @param string|null $details Additional details about the error
     * @param array|null $context Additional context
     * @return self
     */
    public static function invalidCredentials(?string $details = null, ?array $context = null): self
    {
        $message = 'Invalid credentials provided';
        if ($details) {
            $message .= ": $details";
        }
        return new self($message, self::ERROR_INVALID_CREDENTIALS, $context);
    }

    /**
     * Create an expired token exception
     *
     * @param array|null $context Additional context
     * @return self
     */
    public static function expiredToken(?array $context = null): self
    {
        return new self(
            'The authentication token has expired',
            self::ERROR_EXPIRED_TOKEN,
            $context
        );
    }
}