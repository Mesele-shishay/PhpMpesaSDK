<?php

namespace MesaSDK\PhpMpesa;

use RuntimeException;

/**
 * Class MpesaException
 * 
 * Handles M-Pesa API specific exceptions with response codes and messages.
 * 
 * @package MesaSDK\PhpMpesa
 */
class MpesaException extends RuntimeException
{
    /** @var string|null The M-Pesa response code */
    private ?string $responseCode;

    /** @var array|null The full response data */
    private ?array $responseData;

    /**
     * Response code mappings for common M-Pesa errors
     */
    private const RESPONSE_CODES = [
        '0' => 'Success',
        '1' => 'Insufficient Funds',
        '2' => 'Less Than Minimum Transaction Value',
        '3' => 'More Than Maximum Transaction Value',
        '4' => 'Would Exceed Daily Transfer Limit',
        '5' => 'Would Exceed Minimum Balance',
        '6' => 'Unresolved Primary Party',
        '7' => 'Unresolved Receiver Party',
        '8' => 'Would Exceed Maximum Balance',
        '11' => 'Debit Account Invalid',
        '12' => 'Credit Account Invalid',
        '13' => 'Unresolved Debit Account',
        '14' => 'Unresolved Credit Account',
        '15' => 'Duplicate Detected',
        '17' => 'Internal Failure',
        '20' => 'Unresolved Initiator',
        '26' => 'Traffic blocking condition in place',
        '1001' => 'Invalid credentials',
        '1002' => 'Invalid application',
        '1003' => 'Invalid request',
        '1004' => 'Invalid operation type',
        '1005' => 'Invalid merchant',
        '1006' => 'Invalid debit party',
        '1007' => 'Invalid credit party',
        '1008' => 'Invalid currency',
        '1009' => 'Invalid amount',
        '1010' => 'Invalid metadata',
        '1011' => 'Invalid customer number',
        '1012' => 'Invalid reference',
        '1013' => 'Invalid callback URL',
        '1014' => 'Invalid request type',
    ];

    /**
     * MpesaException constructor.
     * 
     * @param string $message Error message
     * @param string|null $responseCode M-Pesa response code
     * @param array|null $responseData Full response data
     * @param int $code Exception code
     * @param \Throwable|null $previous Previous exception
     */
    public function __construct(
        string $message,
        ?string $responseCode = null,
        ?array $responseData = null,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        $this->responseCode = $responseCode;
        $this->responseData = $responseData;

        // If we have a response code and it exists in our mapping, append it to the message
        if ($responseCode && isset(self::RESPONSE_CODES[$responseCode])) {
            $message .= sprintf(' (Response Code %s: %s)', $responseCode, self::RESPONSE_CODES[$responseCode]);
        }

        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the M-Pesa response code
     * 
     * @return string|null
     */
    public function getResponseCode(): ?string
    {
        return $this->responseCode;
    }

    /**
     * Get the full response data
     * 
     * @return array|null
     */
    public function getResponseData(): ?array
    {
        return $this->responseData;
    }

    /**
     * Get the human-readable description for a response code
     * 
     * @param string $code The response code
     * @return string The description or 'Unknown Error' if code not found
     */
    public static function getResponseCodeDescription(string $code): string
    {
        return self::RESPONSE_CODES[$code] ?? 'Unknown Error';
    }

    /**
     * Create an exception from an API response
     * 
     * @param array $response The API response array
     * @param string $defaultMessage Default message if none found in response
     * @return self
     */
    public static function fromResponse(array $response, string $defaultMessage = 'M-Pesa API Error'): self
    {
        $responseCode = $response['ResponseCode'] ?? null;
        $message = $response['errorMessage'] ?? 
                  $response['ResponseDescription'] ?? 
                  $response['errorCode'] ?? 
                  $defaultMessage;

        return new self($message, $responseCode, $response);
    }

    /**
     * Check if the error is a specific type based on response code
     * 
     * @param string $code The response code to check
     * @return bool
     */
    public function isErrorCode(string $code): bool
    {
        return $this->responseCode === $code;
    }

    /**
     * Check if the error is related to insufficient funds
     * 
     * @return bool
     */
    public function isInsufficientFunds(): bool
    {
        return $this->isErrorCode('1');
    }

    /**
     * Check if the error is related to invalid credentials
     * 
     * @return bool
     */
    public function isInvalidCredentials(): bool
    {
        return $this->isErrorCode('1001');
    }

    /**
     * Check if the error is related to duplicate transaction
     * 
     * @return bool
     */
    public function isDuplicateTransaction(): bool
    {
        return $this->isErrorCode('15');
    }
} 