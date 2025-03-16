<?php

namespace MesaSDK\PhpMpesa\Exceptions;

/**
 * Exception thrown when validation errors occur
 */
class ValidationException extends BaseException
{
    /** @var array Validation errors */
    private array $errors;

    /**
     * ValidationException constructor
     *
     * @param string $message Error message
     * @param array $errors Array of validation errors
     * @param array|null $context Additional context
     * @param int $code Error code
     * @param \Throwable|null $previous Previous exception
     */
    public function __construct(
        string $message,
        array $errors = [],
        ?array $context = null,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        $this->errors = $errors;
        parent::__construct($message, $context, $code, $previous);
    }

    /**
     * Get validation errors
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Create exception for missing required field
     *
     * @param string $fieldName Name of the missing field
     * @param array|null $context Additional context
     * @return self
     */
    public static function missingRequiredField(string $fieldName, ?array $context = null): self
    {
        return new self(
            "Missing required field: $fieldName",
            ['required_field' => $fieldName],
            $context
        );
    }

    /**
     * Create exception for invalid field value
     *
     * @param string $fieldName Name of the invalid field
     * @param string $reason Reason why the value is invalid
     * @param mixed $value The invalid value
     * @param array|null $context Additional context
     * @return self
     */
    public static function invalidFieldValue(
        string $fieldName,
        string $reason,
        $value,
        ?array $context = null
    ): self {
        return new self(
            "Invalid value for field '$fieldName': $reason",
            [
                'field' => $fieldName,
                'reason' => $reason,
                'value' => $value
            ],
            $context
        );
    }
}