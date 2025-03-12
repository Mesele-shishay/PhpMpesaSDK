<?php
namespace MesaSDK\PhpMpesa;

use GuzzleHttp\Client;

/**
 * Class Authentication
 * 
 * Handles M-Pesa API authentication by generating and managing access tokens
 * using the provided configuration credentials.
 * 
 * @package MesaSDK\PhpMpesa
 */
class Authentication {
    /** @var string The current access token */
    private string $accessToken = '';

    /** @var Config The configuration instance */
    private Config $config;

    /** @var bool Whether to verify SSL certificates */
    private bool $verifySSL = true;

    /** @var int|null Unix timestamp when the token expires */
    private ?int $expiresAt = null;

    /** @var string|null The type of access token */
    private ?string $tokenType = null;

    /**
     * Authentication constructor.
     * 
     * @param Config $config The configuration instance containing API credentials
     * @param bool $verifySSL Whether to verify SSL certificates (default true)
     */
    public function __construct(Config $config, bool $verifySSL = true) {
        $this->config = $config;
        $this->verifySSL = $verifySSL;
    }

    /**
     * Set whether to verify SSL certificates
     * 
     * @param bool $verify Whether to verify SSL certificates
     * @return self Returns the current instance for method chaining
     */
    public function setVerifySSL(bool $verify): self {
        $this->verifySSL = $verify;
        return $this;
    }

    /**
     * Authenticate with the M-Pesa API and get an access token
     * 
     * @return self Returns the current instance for method chaining
     * @throws \GuzzleHttp\Exception\GuzzleException When the HTTP request fails
     * @throws \RuntimeException When authentication fails or response is invalid
     */
    public function authenticate(): self {
        try {
            $client = new Client([
                'verify' => $this->verifySSL
            ]);
            $response = $client->request('GET', 
                $this->config->getBaseUrl() . "/v1/token/generate?grant_type=client_credentials",
                [
                    'headers' => [
                        'Authorization' => 'Basic ' . base64_encode(
                            $this->config->getConsumerKey() . ":" . $this->config->getConsumerSecret()
                        )
                    ]
                ]
            );

            // Decode the JSON response body into an associative array
            $data = json_decode($response->getBody(), true);
            
            if (!isset($data['access_token'])) {
                throw new \RuntimeException('Failed to get access token from response');
            }

            // Store the access token from the API response
            $this->accessToken = $data['access_token'];
            
            // Store expiration time if provided (expires_in is usually in seconds)
            if (isset($data['expires_in'])) {
                $this->expiresAt = time() + (int)$data['expires_in'];
            }
            
            // Store token type if provided
            if (isset($data['token_type'])) {
                $this->tokenType = $data['token_type'];
            }
            
            return $this;
        } catch (\Exception $e) {
            throw new \RuntimeException('Authentication failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get the current access token
     * 
     * @return string The current access token
     * @throws \RuntimeException If no access token is available (not authenticated)
     */
    public function getToken(): string {
        if (empty($this->accessToken)) {
            throw new \RuntimeException('No access token available. Call authenticate() first.');
        }
        return $this->accessToken;
    }

    /**
     * Check if we have a valid access token
     * 
     * @return bool True if we have an access token, false otherwise
     */
    public function hasToken(): bool {
        return !empty($this->accessToken);
    }

    /**
     * Get the token expiration timestamp
     * 
     * @return int|null Unix timestamp when the token expires, or null if not set
     */
    public function getExpiresAt(): ?int {
        return $this->expiresAt;
    }

    /**
     * Get the token type
     * 
     * @return string|null The token type (e.g. 'Bearer'), or null if not set
     */
    public function getTokenType(): ?string {
        return $this->tokenType;
    }

    /**
     * Check if the token has expired
     * 
     * @return bool True if the token has expired or expiration is unknown, false otherwise
     */
    public function isExpired(): bool {
        if ($this->expiresAt === null) {
            return true;
        }
        return time() >= $this->expiresAt;
    }
}