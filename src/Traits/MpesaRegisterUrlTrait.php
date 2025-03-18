<?php
namespace MesaSDK\PhpMpesa\Traits;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use MesaSDK\PhpMpesa\Responses\MpesaResponse;
use MesaSDK\PhpMpesa\Exceptions\MpesaException;

trait MpesaRegisterUrlTrait
{
    private string $apiEndpoint = "https://apisandbox.safaricom.et/v1/c2b-register-url/register";
    private ?string $lastError = null;
    private ?string $apiKey = null;


    /**
     * Set the API key for authentication
     * @param string $apiKey The API key to use for authentication
     * @return void
     */
    public function setApiKey(string $apiKey): void
    {
        $this->apiKey = $apiKey;
    }

    /**
     * Get the current API key
     * @return string|null The current API key or null if not set
     */
    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    /**
     * Check if API key is set
     * @return bool True if API key is set, false otherwise
     */
    private function hasApiKey(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Register URLs for validation and confirmation
     * @param string $shortCode The M-PESA short code
     * @param string $responseType The response type (Completed/Cancelled)
     * @param string $confirmationUrl The confirmation URL (must be HTTPS)
     * @param string $validationUrl The validation URL (must be HTTPS)
     * @param string $commandId The command ID (default: RegisterURL)
     * @return MpesaResponse The API response
     * @throws MpesaException When any error occurs during the process
     */
    public function registerUrl(
        string $shortCode,
        string $responseType,
        string $confirmationUrl,
        string $validationUrl,
        string $commandId = 'RegisterURL'
    ): MpesaResponse {
        // Validate input parameters
        $this->validateInputs($shortCode, $responseType, $confirmationUrl, $validationUrl);

        $payload = [
            'ShortCode' => $shortCode,
            'ResponseType' => $responseType,
            'CommandID' => $commandId,
            'ConfirmationURL' => $confirmationUrl,
            'ValidationURL' => $validationUrl
        ];

        return $this->makeRequest('POST', '/v1/c2b-register-url/register?apikey=' . $this->apiKey, $payload);
    }

    /**
     * Validate all input parameters
     * @throws MpesaException When validation fails
     */
    private function validateInputs(
        string $shortCode,
        string $responseType,
        string $confirmationUrl,
        string $validationUrl
    ): void {
        // Validate shortcode (numeric and not empty)
        if (!is_numeric($shortCode) || empty($shortCode)) {
            throw new MpesaException(
                'Invalid shortcode provided',
                '1003',
                ['shortCode' => $shortCode]
            );
        }

        // Validate response type
        if (!in_array($responseType, ['Completed', 'Cancelled'])) {
            throw new MpesaException(
                'Response type must be either Completed or Cancelled',
                '1003',
                ['responseType' => $responseType]
            );
        }

        // Validate URLs
        if (
            !filter_var($confirmationUrl, FILTER_VALIDATE_URL) ||
            strpos($confirmationUrl, 'https://') !== 0
        ) {
            throw new MpesaException(
                'Invalid confirmation URL - must be HTTPS',
                '1013',
                ['confirmationUrl' => $confirmationUrl]
            );
        }

        if (
            !filter_var($validationUrl, FILTER_VALIDATE_URL) ||
            strpos($validationUrl, 'https://') !== 0
        ) {
            throw new MpesaException(
                'Invalid validation URL - must be HTTPS',
                '1013',
                ['validationUrl' => $validationUrl]
            );
        }
    }

    /**
     * Make the HTTP request to the M-PESA API
     * @param string $method The HTTP method
     * @param string $endpoint The API endpoint
     * @param array $payload The request payload
     * @return MpesaResponse The API response
     * @throws MpesaException When any error occurs
     */
    private function makeRequest(string $method, string $endpoint, array $payload): MpesaResponse
    {
        if (!$this->hasApiKey()) {
            throw new MpesaException(
                'API key not set',
                '1001',
                ['error' => 'Missing API key']
            );
        }

        try {
            // Append apikey as query parameter to the endpoint
            $endpoint = $this->apiEndpoint;
            $endpoint .= (strpos($endpoint, '?') === false ? '?' : '&') . 'apikey=' . urlencode($this->apiKey);

            $response = $this->client->post($endpoint, [
                'json' => $payload,
                'headers' => [
                    'Content-Type' => 'application/json'
                ]
            ]);

            $decodedResponse = json_decode($response->getBody(), true);
            if (!$decodedResponse) {
                throw new MpesaException(
                    'Failed to decode API response',
                    '1003',
                    ['response' => $response->getBody()]
                );
            }

            // Check for API error responses
            if (isset($decodedResponse['resultCode']) && $decodedResponse['resultCode'] !== '0') {
                throw new MpesaException(
                    $decodedResponse['resultDesc'] ?? 'API Error',
                    $decodedResponse['resultCode'],
                    $decodedResponse
                );
            }

            return new MpesaResponse($decodedResponse);

        } catch (GuzzleException $e) {
            // Try to decode error response if available
            $errorData = [];
            if ($e instanceof \GuzzleHttp\Exception\ClientException) {
                $errorBody = $e->getResponse()->getBody()->getContents();
                $errorData = json_decode($errorBody, true) ?? [];

                if (isset($errorData['resultDesc'])) {
                    throw new MpesaException(
                        $errorData['resultDesc'],
                        $errorData['resultCode'] ?? '1003',
                        $errorData
                    );
                }
            }

            throw new MpesaException(
                $e->getMessage(),
                '1003',
                ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Get the last error message
     */
    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Get the response description from an API response
     * 
     * @param MpesaResponse $response The API response
     * @return string The response description
     */
    public function getResponseDescription(MpesaResponse $response): string
    {
        return $response->getResponseMessage();
    }

    public function registerUrls(string $confirmationUrl, string $validationUrl): array
    {
        $this->validateUrls($confirmationUrl, $validationUrl);

        $response = $this->makeRequest('POST', '/c2b/v1/registerurl', [
            'ShortCode' => $this->config->getShortcode(),
            'ResponseType' => 'Completed',
            'ConfirmationURL' => $confirmationUrl,
            'ValidationURL' => $validationUrl
        ]);

        // Format response to match expected structure
        return [
            'ResponseCode' => '0',
            'ResponseDescription' => $response['ResponseDescription'] ?? 'Success',
            'ConversationID' => $response['ConversationID'] ?? null,
            'OriginatorConversationID' => $response['OriginatorCoversationID'] ?? null
        ];
    }
}