<?php
namespace MesaSDK\PhpMpesa;


use GuzzleHttp\Client;

/**
 * Class Mpesa
 * 
 * Main class for interacting with the M-Pesa API, providing methods for
 * initiating transactions and handling M-Pesa payment operations.
 * 
 * @package MpesaSDK
 */
class Mpesa {
    /** @var Authentication The authentication instance */
    private Authentication $auth;

    /** @var Config The configuration instance */
    private Config $config;

    /** @var string Customer's phone number */
    private string $phoneNumber = '';

    /** @var float Transaction amount */
    private float $amount = 0.0;

    /** @var string Callback URL for transaction results */
    private string $callbackUrl = '';

    /** @var string Description of the transaction */
    private string $transactionDesc = "Payment";

    /** @var string Reference for the transaction */
    private string $accountReference = "123456";

    /**
     * Mpesa constructor.
     * 
     * @param Config|array|null $config Optional configuration instance or array of config values
     * @throws \InvalidArgumentException When invalid configuration is provided
     */
    public function __construct(Config|array|null $config = null) {
        if ($config instanceof Config) {
            $this->config = $config;
        } elseif (is_array($config)) {
            $this->config = $this->createConfigFromArray($config);
        } else {
            $this->config = new Config();
        }
        
        $this->auth = new Authentication($this->config);
    }

    /**
     * Create a Config instance from an array of configuration values
     * 
     * @param array $config Configuration values
     * @return Config
     */
    private function createConfigFromArray(array $config): Config {
        return new Config(
            base_url: $config['base_url'] ?? null,
            consumer_key: $config['consumer_key'] ?? null,
            consumer_secret: $config['consumer_secret'] ?? null,
            passkey: $config['passkey'] ?? null,
            shortcode: $config['shortcode'] ?? null,
            environment: $config['environment'] ?? null
        );
    }

    /**
     * Configure the Mpesa instance directly
     * 
     * @param array $config Configuration values
     * @return self Returns the current instance for method chaining
     */
    public function configure(array $config): self {
        $this->config = $this->createConfigFromArray($config);
        $this->auth = new Authentication($this->config);
        return $this;
    }

    /**
     * Authenticate with the M-Pesa API
     * 
     * @return self Returns the current instance for method chaining
     * @throws \RuntimeException When authentication fails
     */
    public function authenticate(): self {
        $this->auth->authenticate();
        return $this;
    }

    /**
     * Set the customer's phone number
     * 
     * @param string $phone Phone number in the format 2517XXXXXXXX
     * @return self Returns the current instance for method chaining
     * @throws \InvalidArgumentException If phone number format is invalid
     */
    public function setPhoneNumber(string $phone): self {
        // Basic validation for Kenyan phone numbers
        if (!preg_match('/^251[17]\d{8}$/', $phone)) {
            throw new \InvalidArgumentException('Phone number must be in the format 251XXXXXXXXX');
        }
        $this->phoneNumber = $phone;
        return $this;
    }

    /**
     * Set the transaction amount
     * 
     * @param float $amount Amount to charge
     * @return self Returns the current instance for method chaining
     * @throws \InvalidArgumentException If amount is not positive
     */
    public function setAmount(float $amount): self {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be greater than 0');
        }
        $this->amount = $amount;
        return $this;
    }

    /**
     * Set the callback URL for transaction results
     * 
     * @param string $url Valid HTTPS URL
     * @return self Returns the current instance for method chaining
     * @throws \InvalidArgumentException If URL is invalid or not HTTPS
     */
    public function setCallbackUrl(string $url): self {
        if (!filter_var($url, FILTER_VALIDATE_URL) || !str_starts_with($url, 'https://')) {
            throw new \InvalidArgumentException('Callback URL must be a valid HTTPS URL');
        }
        $this->callbackUrl = $url;
        return $this;
    }

    /**
     * Set the transaction description
     * 
     * @param string $desc Description of the transaction
     * @return self Returns the current instance for method chaining
     */
    public function setTransactionDesc(string $desc): self {
        $this->transactionDesc = $desc;
        return $this;
    }

    /**
     * Set the account reference
     * 
     * @param string $reference Reference for the transaction
     * @return self Returns the current instance for method chaining
     */
    public function setAccountReference(string $reference): self {
        $this->accountReference = $reference;
        return $this;
    }

    /**
     * Set the SSL verification flag
     * 
     * @param bool $verify SSL verification flag
     * @return self Returns the current instance for method chaining
     */
    public function setVerifySSL(bool $verify): self {
        $this->auth->setVerifySSL($verify);
        return $this;
    }

    /**
     * Initiate an STK Push request
     * 
     * @return array The API response
     * @throws \RuntimeException When the request fails or required fields are missing
     */
    public function initiateSTKPush(): array {
        $this->validateRequiredFields();

        try {
            $timestamp = date('YmdHis');
            $password = $this->generatePassword($timestamp);

            $client = new Client();
            $response = $client->request('POST', 
                $this->config->getBaseUrl() . "/mpesa/stkpush/v1/processrequest",
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->auth->getToken(),
                        'Content-Type' => 'application/json'
                    ],
                    'json' => [
                        "BusinessShortCode" => $this->config->getShortcode(),
                        "Password" => $password,
                        "Timestamp" => $timestamp,
                        "TransactionType" => "CustomerPayBillOnline",
                        "Amount" => $this->amount,
                        "PartyA" => $this->phoneNumber,
                        "PartyB" => $this->config->getShortcode(),
                        "PhoneNumber" => $this->phoneNumber,
                        "CallBackURL" => $this->callbackUrl,
                        "AccountReference" => $this->accountReference,
                        "TransactionDesc" => $this->transactionDesc
                    ]
                ]
            );

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            throw new \RuntimeException('STK Push request failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Generate the password for the STK Push request
     * 
     * @param string $timestamp Current timestamp in YmdHis format
     * @return string Base64 encoded password
     */
    private function generatePassword(string $timestamp): string {
        return base64_encode(
            $this->config->getShortcode() . 
            $this->config->getPasskey() . 
            $timestamp
        );
    }

    /**
     * Validate that all required fields are set before making a request
     * 
     * @throws \RuntimeException If any required field is missing
     */
    private function validateRequiredFields(): void {
        if (empty($this->phoneNumber)) {
            throw new \RuntimeException('Phone number is required');
        }
        if ($this->amount <= 0) {
            throw new \RuntimeException('Amount must be set and greater than 0');
        }
        if (empty($this->callbackUrl)) {
            throw new \RuntimeException('Callback URL is required');
        }
        if (!$this->auth->hasToken()) {
            throw new \RuntimeException('Authentication token is required. Call authenticate() first');
        }
    }
}