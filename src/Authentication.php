<?php
namespace MesaSDK\PhpMpesa;

use GuzzleHttp\Client;
use MesaSDK\PhpMpesa\Exceptions\MpesaException;

/**
 * Class Authentication
 * 
 * Handles M-Pesa API authentication by generating and managing access tokens
 * using the provided configuration credentials.
 * 
 * @package MesaSDK\PhpMpesa
 */
class Authentication
{
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

    /** @var Client|null The HTTP client instance */
    private ?Client $client = null;

    /**
     * Maps M-Pesa authentication error codes to their descriptions and solutions
     * 
     * @var array<string, array{description: string, cause: string, mitigation: string}>
     */
    private const ERROR_CODES = [
        '999991' => [
            'description' => 'Invalid client id',
            'cause' => 'Incorrect basic Authorization username',
            'mitigation' => 'Input the correct username'
        ],
        '999996' => [
            'description' => 'Invalid Authentication',
            'cause' => 'Incorrect authorization type',
            'mitigation' => 'Select type as Basic Auth'
        ],
        '999997' => [
            'description' => 'Invalid Authorization Header',
            'cause' => 'Incorrect basic authorization password',
            'mitigation' => 'Input the correct password'
        ],
        '999998' => [
            'description' => 'Missing Authorization Header',
            'cause' => 'Authorization header not present',
            'mitigation' => 'Add Authorization header'
        ],
        '999999' => [
            'description' => 'Invalid Access Token',
            'cause' => 'Access token has expired or is invalid',
            'mitigation' => 'Generate a new access token'
        ]
    ];

    /**
     * Authentication constructor.
     * 
     * @param Config $config The configuration instance
     * @param bool $verifySSL Whether to verify SSL certificates
     */
    public function __construct(Config $config, bool $verifySSL = true)
    {
        $this->config = $config;
        $this->verifySSL = $verifySSL;
    }

    /**
     * Set the HTTP client instance for testing purposes
     * 
     * @param Client $client The HTTP client instance
     * @return self
     */
    public function setClient(Client $client): self
    {
        $this->client = $client;
        return $this;
    }

    /**
     * Get the HTTP client instance
     * 
     * @return Client
     */
    private function getClient(): Client
    {
        if (!$this->client) {
            $this->client = new Client(['verify' => $this->verifySSL]);
        }
        return $this->client;
    }

    /**
     * Set whether to verify SSL certificates
     * 
     * @param bool $verify Whether to verify SSL certificates
     * @return self Returns the current instance for method chaining
     */
    public function setVerifySSL(bool $verify): self
    {
        $this->verifySSL = $verify;
        return $this;
    }

    /**
     * Get whether to verify SSL certificates
     * 
     * @return bool Returns true if SSL certificates are verified, false otherwise
     */
    public function getVerifySSL(): bool
    {
        return $this->verifySSL;
    }

    /**
     * Authenticate with the M-Pesa API and get an access token
     * 
     * @return string The access token
     * @throws MpesaException When authentication fails
     */
    public function authenticate(): string
    {
        // Return cached token if it's still valid
        if ($this->hasToken() && !$this->isExpired()) {
            return $this->accessToken;
        }

        try {
            // Validate configuration before making request
            $this->config->validate();
            $options = [
                'headers' => [
                    'Authorization' => $this->generateBasicAuth(),
                    'Accept' => 'application/json',
                ]
            ];

            $response = $this->getClient()->request(
                'GET',
                $this->config->getBaseUrl() . '/v1/token/generate?grant_type=client_credentials',
                $options
            );
            $data = json_decode($response->getBody(), true);

            if (!$data || !isset($data['access_token'])) {
                throw new MpesaException('Invalid response from authentication endpoint');
            }

            $this->accessToken = $data['access_token'];
            $this->expiresAt = time() + ($data['expires_in'] ?? 3599);
            $this->tokenType = $data['token_type'] ?? 'Bearer';

            return $this->accessToken;
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            $data = json_decode($response->getBody(), true);

            if (isset($data['errorCode']) && $this->isAuthenticationError($data['errorCode'])) {
                $errorDetails = $this->getErrorDetails($data['errorCode']);
                throw new MpesaException(
                    $errorDetails['description'] ?? $data['errorMessage'],
                    $response->getStatusCode(),
                    ['error_details' => $errorDetails]
                );
            }


            throw new MpesaException(
                $data['errorMessage'] ?? 'Authentication failed',
                $response->getStatusCode()
            );
        } catch (\Exception $e) {
            throw new MpesaException(
                'Authentication failed: ' . $e->getMessage(),
                $e->getCode() ?: 500
            );
        }
    }

    /**
     * Generate the Basic Auth header value
     * 
     * @return string The Basic Auth header value
     */
    public function generateBasicAuth(): string
    {
        $credentials = base64_encode(
            $this->config->getConsumerKey() . ':' . $this->config->getConsumerSecret()
        );
        return 'Basic ' . $credentials;
    }

    /**
     * Get the current access token
     * 
     * @return string The current access token
     * @throws \RuntimeException If no access token is available (not authenticated)
     */
    public function getToken(): string
    {
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
    public function hasToken(): bool
    {
        return !empty($this->accessToken);
    }

    /**
     * Get the token expiration timestamp
     * 
     * @return int|null Unix timestamp when the token expires, or null if not set
     */
    public function getExpiresAt(): ?int
    {
        return $this->expiresAt;
    }

    /**
     * Get the token type
     * 
     * @return string|null The token type (e.g. 'Bearer'), or null if not set
     */
    public function getTokenType(): ?string
    {
        return $this->tokenType;
    }

    /**
     * Check if the token has expired
     * 
     * @return bool True if the token has expired or expiration is unknown, false otherwise
     */
    public function isExpired(): bool
    {
        if ($this->expiresAt === null) {
            return true;
        }
        return time() >= $this->expiresAt;
    }

    /**
     * Get detailed error information for a specific error code
     * 
     * @param string $code The error code to look up
     * @return array{description: string, cause: string, mitigation: string}|null Error details or null if code not found
     */
    public function getErrorDetails(string $code): ?array
    {
        return self::ERROR_CODES[$code] ?? null;
    }

    /**
     * Check if an error code is a known authentication error
     * 
     * @param string $code The error code to check
     * @return bool True if the code is a known authentication error
     */
    public function isAuthenticationError(string $code): bool
    {
        return isset(self::ERROR_CODES[$code]);
    }
}