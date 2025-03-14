<?php

namespace MesaSDK\PhpMpesa\Responses;

class MpesaResponse
{
    private array $rawResponse;
    private int $responseCode;
    private string $responseMessage;
    private string $customerMessage;
    private string $timestamp;

    /**
     * Create a new MpesaResponse instance
     * @param array $response The raw response from the API
     */
    public function __construct(array $response)
    {
        $this->rawResponse = $response;
        $this->parseResponse();
    }

    /**
     * Parse the raw response into properties
     */
    private function parseResponse(): void
    {
        $header = $this->rawResponse['header'] ?? [];

        $this->responseCode = $header['responseCode'] ?? 400;
        $this->responseMessage = $header['responseMessage'] ?? 'Unknown error';
        $this->customerMessage = $header['customerMessage'] ?? 'Unknown error';
        $this->timestamp = $header['timestamp'] ?? date('Y-m-d\TH:i:s.v');
    }

    /**
     * Check if the response was successful
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return $this->responseCode === 200;
    }

    /**
     * Get the response code
     * @return int
     */
    public function getResponseCode(): int
    {
        return $this->responseCode;
    }

    /**
     * Get the response message
     * @return string
     */
    public function getResponseMessage(): string
    {
        return $this->responseMessage;
    }

    /**
     * Get the customer message
     * @return string
     */
    public function getCustomerMessage(): string
    {
        return $this->customerMessage;
    }

    /**
     * Get the timestamp
     * @return string
     */
    public function getTimestamp(): string
    {
        return $this->timestamp;
    }

    /**
     * Get the raw response
     * @return array
     */
    public function getRawResponse(): array
    {
        return $this->rawResponse;
    }

    /**
     * Create an error response
     * @param string $message The error message
     * @param int $code The error code (default: 400)
     * @return static
     */
    public static function error(string $message, int $code = 400): self
    {
        return new self([
            'header' => [
                'responseCode' => $code,
                'responseMessage' => $message,
                'customerMessage' => $message,
                'timestamp' => date('Y-m-d\TH:i:s.v')
            ]
        ]);
    }

    /**
     * Convert the response to an array
     * @return array
     */
    public function toArray(): array
    {
        return [
            'header' => [
                'responseCode' => $this->responseCode,
                'responseMessage' => $this->responseMessage,
                'customerMessage' => $this->customerMessage,
                'timestamp' => $this->timestamp
            ]
        ];
    }

    /**
     * Get the response data
     * @return array
     */
    public function getData(): array
    {
        return $this->rawResponse['data'] ?? $this->rawResponse;
    }
}