<?php
namespace MesaSDK\PhpMpesa;

use GuzzleHttp\Client;
use MesaSDK\PhpMpesa\Exceptions\MpesaException;
use MesaSDK\PhpMpesa\Traits\HttpClientTrait;

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
    use HttpClientTrait;

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
     * @param Config $config Configuration instance
     * @param bool $verifySSL Whether to verify SSL certificates
     */
    public function __construct(Config $config, bool $verifySSL = true)
    {
        $this->config = $config;
        $this->verifySSL = $verifySSL;
        $this->client = $this->createHttpClient($config);
    }

    /**
     * Set the HTTP client instance
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
    protected function getClient(): Client
    {
        if (!$this->client) {
            $this->client = $this->createHttpClient($this->config);
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
     * Authenticate with the M-Pesa API
     * 
     * @return string The access token
     * @throws MpesaException
     */
    public function authenticate(): string
    {
        if (!$this->isExpired()) {
            return $this->accessToken;
        }

        $url = $this->config->getBaseUrl() . '/v1/token/generate?grant_type=client_credentials';
        $options = [
            'headers' => [
                'Authorization' => $this->generateBasicAuth(),
                'Accept' => 'application/json',
            ]
        ];

        try {
            $response = $this->executeWithRetry(
                $this->getClient(),
                'GET',
                $url,
                $options,
                $this->config
            );

            if (isset($response['access_token'])) {
                $this->accessToken = $response['access_token'];
                $this->expiresAt = time() + ($response['expires_in'] ?? 3599);
                $this->tokenType = $response['token_type'] ?? 'Bearer';
                return $this->accessToken;
            }

            throw new MpesaException('Failed to get access token: Invalid response format');
        } catch (MpesaException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new MpesaException(
                'Failed to get access token: ' . $e->getMessage(),
                0,
                ['error' => $e->getMessage()]
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
        if (empty($this->accessToken) && $this->config->getAutoAuthenticate()) {
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

    /**
     * Get the HTTP client instance for external use
     * 
     * @return Client
     */
    public function getHttpClient(): Client
    {
        return $this->getClient();
    }

    /**
     * Make an authenticated request to the M-Pesa API
     * 
     * @param string $method HTTP method
     * @param string $endpoint API endpoint
     * @param array $payload Request payload
     * @return array|string Response from the API
     * @throws MpesaException
     */
    public function makeRequest(string $method, string $endpoint, array $payload): array|string
    {
        try {
            $response = $this->client->request($method, $this->config->getBaseUrl() . $endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->getToken(),
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            // If it's an error response, return just the description
            if (isset($result['resultDesc']) && isset($result['resultCode']) && $result['resultCode'] !== '0') {
                return $result['resultDesc'];
            }

            return $result;
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            if ($e->hasResponse()) {
                $error = json_decode($e->getResponse()->getBody()->getContents(), true);
                throw new MpesaException($error['resultDesc'] ?? $e->getMessage());
            }
            throw new MpesaException($e->getMessage());
        } catch (\Exception $e) {
            throw new MpesaException($e->getMessage());
        }
    }
}