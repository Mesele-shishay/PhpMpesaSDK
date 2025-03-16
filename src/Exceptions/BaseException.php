<?php

namespace MesaSDK\PhpMpesa\Exceptions;

use RuntimeException;

/**
 * Base exception class for all SDK exceptions
 */
abstract class BaseException extends RuntimeException
{
    /** @var array|null Additional error context */
    protected ?array $context;

    /**
     * BaseException constructor
     *
     * @param string $message Error message
     * @param array|null $context Additional error context
     * @param int $code Error code
     * @param \Throwable|null $previous Previous exception
     */
    public function __construct(
        string $message,
        ?array $context = null,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        $this->context = $context;
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get additional error context
     *
     * @return array|null
     */
    public function getContext(): ?array
    {
        return $this->context;
    }
}