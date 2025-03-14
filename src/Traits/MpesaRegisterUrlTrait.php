<?php
namespace MesaSDK\PhpMpesa\Traits;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use MesaSDK\PhpMpesa\Responses\MpesaResponse;

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
     * @return MpesaResponse Response object containing response details
     */
    public function register(
        string $shortCode,
        string $responseType,
        string $confirmationUrl,
        string $validationUrl,
        string $commandId = 'RegisterURL'
    ): MpesaResponse {
        // Validate input parameters
        if (!$this->validateInputs($shortCode, $responseType, $confirmationUrl, $validationUrl)) {
            return MpesaResponse::error($this->lastError);
        }

        $payload = [
            'ShortCode' => $shortCode,
            'ResponseType' => $responseType,
            'CommandID' => $commandId,
            'ConfirmationURL' => $confirmationUrl,
            'ValidationURL' => $validationUrl
        ];

        return $this->makeRequest($payload);
    }

    /**
     * Validate all input parameters
     */
    private function validateInputs(
        string $shortCode,
        string $responseType,
        string $confirmationUrl,
        string $validationUrl
    ): bool {
        // Validate shortcode (numeric and not empty)
        if (!is_numeric($shortCode) || empty($shortCode)) {
            $this->lastError = 'Invalid shortcode provided';
            return false;
        }

        // Validate response type
        if (!in_array($responseType, ['Completed', 'Cancelled'])) {
            $this->lastError = 'Response type must be either Completed or Cancelled';
            return false;
        }

        // Validate URLs
        if (
            !filter_var($confirmationUrl, FILTER_VALIDATE_URL) ||
            strpos($confirmationUrl, 'https://') !== 0
        ) {
            $this->lastError = 'Invalid confirmation URL - must be HTTPS';
            return false;
        }

        if (
            !filter_var($validationUrl, FILTER_VALIDATE_URL) ||
            strpos($validationUrl, 'https://') !== 0
        ) {
            $this->lastError = 'Invalid validation URL - must be HTTPS';
            return false;
        }

        return true;
    }

    /**
     * Make the HTTP request to the M-PESA API
     * @return MpesaResponse
     */
    private function makeRequest(array $payload): MpesaResponse
    {
        if (!$this->hasApiKey()) {
            return MpesaResponse::error('API key not set');
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
                return MpesaResponse::error('Failed to decode API response');
            }

            return new MpesaResponse($decodedResponse);

        } catch (GuzzleException $e) {
            return MpesaResponse::error($e->getMessage());
        }
    }

    /**
     * Get the last error message
     */
    public function getLastError(): ?string
    {
        return $this->lastError;
    }
}