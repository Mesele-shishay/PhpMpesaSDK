<?php

namespace MesaSDK\PhpMpesa\Base;

use MesaSDK\PhpMpesa\Config;
use MesaSDK\PhpMpesa\Authentication;
use MesaSDK\PhpMpesa\Contracts\MpesaInterface;
use MesaSDK\PhpMpesa\Exceptions\MpesaException;

abstract class BaseMpesa implements MpesaInterface
{

    /** @var Authentication The authentication instance */
    protected Authentication $auth;

    /** @var Config The configuration instance */
    protected Config $config;

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

    /**
     * BaseMpesa constructor.
     * 
     * @param Config|array|null $config Optional configuration instance or array of config values
     * @throws \InvalidArgumentException When invalid configuration is provided
     */
    public function __construct($config = null)
    {
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

    public function setPhoneNumber(string $phone): self
    {
        if (!preg_match('/^251[17]\d{8}$/', $phone)) {
            throw new \InvalidArgumentException('Phone number must be in the format 251XXXXXXXXX');
        }
        $this->phoneNumber = $phone;
        return $this;
    }

    public function setAmount(float $amount): self
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be greater than 0');
        }
        $this->amount = $amount;
        return $this;
    }

    public function setCallbackUrl(string $url): self
    {
        if (!filter_var($url, FILTER_VALIDATE_URL) || !str_starts_with($url, 'https://')) {
            throw new \InvalidArgumentException('Callback URL must be a valid HTTPS URL');
        }
        $this->callbackUrl = $url;
        return $this;
    }

    public function setTransactionDesc(string $desc): self
    {
        $this->transactionDesc = $desc;
        return $this;
    }

    public function setAccountReference(string $reference): self
    {
        $this->accountReference = $reference;
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

    public function isSuccessful(): bool
    {
        return isset($this->response['ResponseCode']) && $this->response['ResponseCode'] === '0';
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
}