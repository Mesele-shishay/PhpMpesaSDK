<?php

namespace MesaSDK\PhpMpesa\Base;

use MesaSDK\PhpMpesa\Config;
use MesaSDK\PhpMpesa\Authentication;
use MesaSDK\PhpMpesa\Contracts\MpesaInterface;
use MesaSDK\PhpMpesa\Exceptions\MpesaException;
use MesaSDK\PhpMpesa\Logging\MpesaLogger;
use MesaSDK\PhpMpesa\Traits\HttpClientTrait;
use MesaSDK\PhpMpesa\Traits\B2CTrait;

abstract class BaseMpesa implements MpesaInterface
{
    use HttpClientTrait;
    use B2CTrait;

    /** @var Authentication The authentication instance */
    protected Authentication $auth;

    /** @var Config The configuration instance */
    protected Config $config;

    /** @var MpesaLogger The logger instance */
    protected MpesaLogger $logger;

    /** @var string Customer's phone number */
    protected string $phoneNumber = '';

    /** @var float Transaction amount */
    protected float $amount = 0.0;

    /** @var string Callback URL for transaction results */
    protected string $callbackUrl = '';

    /** @var string Description of the transaction */
    protected string $transactionDesc = "Payment";

    /** @var string Reference for the transaction */
    protected string $accountReference = "123456";

    /** @var array|null The response from the API request */
    protected ?array $response = null;

    /** @var string The command ID for B2C transactions */
    protected string $commandID = 'BusinessPayment';

    /** @var string|null The security credential */
    protected ?string $securityCredential = null;

    /** @var string|null The name of the initiator */
    protected ?string $initiatorName = null;

    /**
     * BaseMpesa constructor.
     * 
     * @param Config|array|null $config Optional configuration instance or array of config values
     * @throws \InvalidArgumentException When invalid configuration is provided
     */
    public function __construct($config = null)
    {
        // Initialize config first
        if ($config instanceof Config) {
            $this->config = $config;
        } elseif (is_array($config)) {
            $this->config = $this->createConfigFromArray($config);
        } else {
            $this->config = new Config();
        }

        // Ensure logging configuration is set with defaults
        if (empty($this->config->getLoggingConfig())) {
            $this->config->setLoggingConfig([]);
        }

        // Initialize logger with config
        $this->logger = new MpesaLogger($this->config);

        // Initialize authentication
        $this->auth = new Authentication($this->config);
    }

    /**
     * Create a Config instance from an array of configuration values
     */
    protected function createConfigFromArray(array $config): Config
    {
        return new Config(
            $config['base_url'] ?? null,
            $config['consumer_key'] ?? null,
            $config['consumer_secret'] ?? null,
            $config['passkey'] ?? null,
            $config['shortcode'] ?? null,
            $config['environment'] ?? null
        );
    }

    /**
     * Set the logger instance
     * 
     * @param MpesaLogger $logger The logger instance
     * @return self
     */
    public function setLogger(MpesaLogger $logger): self
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * Get the authentication instance
     * 
     * @return Authentication
     */
    public function getAuth(): Authentication
    {
        return $this->auth;
    }

    /**
     * Get the logger instance
     * 
     * @return MpesaLogger
     */
    public function getLogger(): MpesaLogger
    {
        return $this->logger;
    }

    public function setPhoneNumber(string $phone): self
    {
        if (!preg_match('/^251[17]\d{8}$/', $phone)) {
            $this->logger->warning('Invalid phone number format', ['phone' => $phone]);
            throw new \InvalidArgumentException('Phone number must be in the format 251XXXXXXXXX');
        }
        $this->phoneNumber = $phone;
        $this->logger->debug('Phone number set', ['phone' => $phone]);
        return $this;
    }

    public function setAmount(float $amount): self
    {
        if ($amount <= 0) {
            $this->logger->warning('Invalid amount', ['amount' => $amount]);
            throw new \InvalidArgumentException('Amount must be greater than 0');
        }
        $this->amount = $amount;
        $this->logger->debug('Amount set', ['amount' => $amount]);
        return $this;
    }

    public function setCallbackUrl(string $url): self
    {
        if (!filter_var($url, FILTER_VALIDATE_URL) || strpos($url, 'https://') !== 0) {
            $this->logger->warning('Invalid callback URL', ['url' => $url]);
            throw new \InvalidArgumentException('Callback URL must be a valid HTTPS URL');
        }
        $this->callbackUrl = $url;
        $this->logger->debug('Callback URL set', ['url' => $url]);
        return $this;
    }

    public function setTransactionDesc(string $desc): self
    {
        $this->transactionDesc = $desc;
        $this->logger->debug('Transaction description set', ['description' => $desc]);
        return $this;
    }

    public function setAccountReference(string $reference): self
    {
        $this->accountReference = $reference;
        $this->logger->debug('Account reference set', ['reference' => $reference]);
        return $this;
    }

    public function setInitiatorName(string $initiatorName): self
    {
        $this->initiatorName = $initiatorName;
        $this->logger->debug('Initiator name set', ['initiator_name' => $initiatorName]);
        return $this;
    }

    public function setSecurityCredential(string $securityCredential): self
    {
        $this->securityCredential = $securityCredential;
        $this->logger->debug('Security credential set');
        return $this;
    }

    public function setCommandID(string $commandId): self
    {
        $validCommands = ['BusinessPayment', 'SalaryPayment', 'PromotionPayment'];
        if (!in_array($commandId, $validCommands)) {
            throw new \InvalidArgumentException('Invalid CommandID. Must be one of: ' . implode(', ', $validCommands));
        }
        $this->commandID = $commandId;
        return $this;
    }

    public function authenticate(): self
    {
        try {
            $this->auth->authenticate();
        } catch (\Exception $e) {
            $this->response = [
                'errorMessage' => $e->getMessage()
            ];
            throw new \RuntimeException('Authentication failed: ' . $e->getMessage(), 0, $e);
        }
        return $this;
    }

    /**
     * Get the raw response for debugging
     */
    public function getRawResponse(): ?array
    {
        return $this->response;
    }

    public function getErrorMessage(): string
    {
        if (isset($this->response['errorMessage'])) {
            return $this->response['errorMessage'];
        }
        if (isset($this->response['errorCode'])) {
            return "Error Code: " . $this->response['errorCode'];
        }
        return "Unknown error occurred.";
    }

    public function getMerchantRequestID(): ?string
    {
        return $this->response['MerchantRequestID'] ?? null;
    }

    public function getCheckoutRequestID(): ?string
    {
        return $this->response['CheckoutRequestID'] ?? null;
    }

    /**
     * Validate that all required fields are set before making a request
     */
    protected function validateRequiredFields(): void
    {
        $errors = [];

        if (empty($this->phoneNumber)) {
            $errors[] = 'Phone number is required';
        }
        if ($this->amount <= 0) {
            $errors[] = 'Amount must be set and greater than 0';
        }
        if (empty($this->callbackUrl)) {
            $errors[] = 'Callback URL is required';
        }
        if (!$this->auth->hasToken()) {
            $errors[] = 'Authentication token is required. Call authenticate() first';
        }

        if (!empty($errors)) {
            $this->response = [
                'errorMessage' => implode(', ', $errors),
                'errorCode' => '1003'
            ];
            throw new MpesaException(
                'Validation failed: ' . implode(', ', $errors),
                '1003',
                $this->response
            );
        }
    }

    /**
     * Log an API request
     * 
     * @param string $endpoint The API endpoint
     * @param array $payload The request payload
     * @param array $headers The request headers
     */
    protected function logApiRequest(string $endpoint, array $payload, array $headers = []): void
    {
        $this->logger->logRequest($endpoint, $payload, $headers);
    }

    /**
     * Log an API response
     * 
     * @param string $endpoint The API endpoint
     * @param mixed $response The response data
     * @param int $statusCode The HTTP status code
     */
    protected function logApiResponse(string $endpoint, $response, int $statusCode): void
    {
        $this->logger->logResponse($endpoint, $response, $statusCode);
    }

    /**
     * Execute an API request
     * 
     * @param string $method HTTP method
     * @param string $endpoint API endpoint
     * @param array $payload Request payload
     * @return array|string Response from the API
     * @throws MpesaException
     */
    protected function executeRequest(string $method, string $endpoint, array $payload): array|string
    {
        try {
            // Ensure we have a valid access token only if auto_authenticate is enabled
            if ($this->auth->isExpired() && $this->config->getAutoAuthenticate()) {
                $this->authenticate();
            } elseif ($this->auth->isExpired()) {
                throw new MpesaException('Authentication token is expired. Please call authenticate() manually.');
            }

            $response = $this->auth->makeRequest($method, $endpoint, $payload);

            // If response contains resultDesc, return just that for error cases
            if (isset($response['resultDesc']) && isset($response['resultCode']) && $response['resultCode'] !== '0') {
                return $response['resultDesc'];
            }

            return $response;
        } catch (\Exception $e) {
            throw new MpesaException($e->getMessage());
        }
    }

    abstract public function setAccountBalanceInitiator(string $initiator): self;
    abstract public function setAccountBalancePartyA(string $partyA): self;
    abstract public function setAccountBalanceRemarks(string $remarks): self;
    abstract public function setAccountBalanceIdentifierType(string $identifierType): self;
    abstract public function checkAccountBalance(): array;
    abstract public function setQueueTimeOutUrl(string $url): self;
    abstract public function setResultUrl(string $url): self;
}