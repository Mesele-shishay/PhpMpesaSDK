<?php

namespace MesaSDK\PhpMpesa\Responses;

class MpesaResponse implements \ArrayAccess
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
        $data = $this->rawResponse['data'] ?? [];

        $this->responseCode = $header['responseCode'] ?? ($data['ResponseCode'] === '0' ? 200 : 400);
        $this->responseMessage = $header['responseMessage'] ?? $data['ResponseDescription'] ?? 'Unknown error';
        $this->customerMessage = $header['customerMessage'] ?? $data['CustomerMessage'] ?? $data['ResponseDescription'] ?? 'Unknown error';
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
     * Get data from the response
     * @return array
     */
    public function getData(): array
    {
        return $this->rawResponse['data'] ?? [];
    }

    public function offsetExists($offset): bool
    {
        return isset($this->rawResponse[$offset]);
    }

    public function offsetGet($offset): mixed
    {
        return $this->rawResponse[$offset] ?? null;
    }

    public function offsetSet($offset, $value): void
    {
        $this->rawResponse[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->rawResponse[$offset]);
    }
}