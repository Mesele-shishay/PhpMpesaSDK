<?php

class MpesaRegisterUrl
{
    private string $apiEndpoint;
    private string $apiKey;
    private array $config;
    private ?string $lastError = null;

    /**
     * MpesaRegisterUrl constructor.
     * @param string $apiKey The API key for authentication
     * @param string $environment 'sandbox' or 'production'
     */
    public function __construct(string $apiKey, string $environment = 'sandbox')
    {
        $this->apiKey = $apiKey;
        $this->apiEndpoint = $environment === 'sandbox' 
            ? 'https://sandbox.safaricom.co.ke/mpesa/c2b/v1/registerurl'
            : 'https://api.safaricom.co.ke/mpesa/c2b/v1/registerurl';
    }

    /**
     * Register URLs for validation and confirmation
     * @param string $shortCode The M-PESA short code
     * @param string $responseType The response type (Completed/Cancelled)
     * @param string $confirmationUrl The confirmation URL (must be HTTPS)
     * @param string $validationUrl The validation URL (must be HTTPS)
     * @return array|false Response array on success, false on failure
     */
    public function register(
        string $shortCode,
        string $responseType,
        string $confirmationUrl,
        string $validationUrl
    ) {
        // Validate input parameters
        if (!$this->validateInputs($shortCode, $responseType, $confirmationUrl, $validationUrl)) {
            return false;
        }

        $payload = [
            'ShortCode' => $shortCode,
            'ResponseType' => $responseType,
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
        if (!filter_var($confirmationUrl, FILTER_VALIDATE_URL) || 
            !str_starts_with($confirmationUrl, 'https://')) {
            $this->lastError = 'Invalid confirmation URL - must be HTTPS';
            return false;
        }

        if (!filter_var($validationUrl, FILTER_VALIDATE_URL) || 
            !str_starts_with($validationUrl, 'https://')) {
            $this->lastError = 'Invalid validation URL - must be HTTPS';
            return false;
        }

        return true;
    }

    /**
     * Make the HTTP request to the M-PESA API
     */
    private function makeRequest(array $payload): array|false
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->apiEndpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json'
            ],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response === false) {
            $this->lastError = curl_error($ch);
            curl_close($ch);
            return false;
        }

        curl_close($ch);
        
        $decodedResponse = json_decode($response, true);
        
        if ($httpCode !== 200 || !$decodedResponse) {
            $this->lastError = 'API request failed: ' . ($decodedResponse['errorMessage'] ?? 'Unknown error');
            return false;
        }

        return $decodedResponse;
    }

    /**
     * Get the last error message
     */
    public function getLastError(): ?string
    {
        return $this->lastError;
    }
} 