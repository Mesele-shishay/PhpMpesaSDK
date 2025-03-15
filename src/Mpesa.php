<?php
namespace MesaSDK\PhpMpesa;

use GuzzleHttp\Client;
use MesaSDK\PhpMpesa\Traits\MpesaRegisterUrlTrait;
use MesaSDK\PhpMpesa\Traits\STKPushTrait;
use MesaSDK\PhpMpesa\Base\BaseMpesa;
use MesaSDK\PhpMpesa\Traits\B2CTrait;
use MesaSDK\PhpMpesa\Traits\CommonTrait;
/**
 * Class Mpesa
 * 
 * Main class for interacting with the M-Pesa API, providing methods for
 * initiating transactions and handling M-Pesa payment operations.
 * 
 * @package MpesaSDK
 */
class Mpesa extends BaseMpesa
{
    use CommonTrait, STKPushTrait, MpesaRegisterUrlTrait, B2CTrait {
        CommonTrait::isSuccessful insteadof STKPushTrait, B2CTrait;
        CommonTrait::getResultCode insteadof STKPushTrait, B2CTrait;
        CommonTrait::getResultDesc insteadof STKPushTrait, B2CTrait;
        CommonTrait::getCallbackData insteadof STKPushTrait, B2CTrait;
    }

    private Client $client;

    /** @var string Customer's phone number */
    protected string $phoneNumber = '';

    /** @var string|null The name of the initiator */
    protected ?string $initiatorName = null;

    /**
     * Mpesa constructor.
     * 
     * @param Config|array|null $config Optional configuration instance or array of config values
     * @throws \InvalidArgumentException When invalid configuration is provided
     */
    public function __construct($config = null)
    {
        // Call parent constructor first to initialize config and logger
        parent::__construct($config);

        // Initialize Guzzle client with SSL verification setting
        $this->client = new Client(['verify' => $this->config->getVerifySSL()]);

        // Re-initialize authentication with SSL verification
        $this->auth = new Authentication($this->config, $this->config->getVerifySSL());
    }

    /**
     * Create a Config instance from an array of configuration values
     * 
     * @param array $config Configuration values
     * @return Config
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
     * Initiate an STK Push request
     * 
     * @param float $amount Amount to charge
     * @param string $phone Phone number in the format 2547XXXXXXXX
     * @param string $reference Account reference
     * @param string $description Transaction description
     * @return \MesaSDK\PhpMpesa\Contracts\MpesaInterface Returns the current instance for method chaining
     * @throws \InvalidArgumentException When parameters are invalid
     * @throws \RuntimeException When the request fails
     */
    public function stkPush(float $amount, string $phone, string $reference, string $description): \MesaSDK\PhpMpesa\Contracts\MpesaInterface
    {
        $this->setAmount($amount)
            ->setPhoneNumber($phone)
            ->setAccountReference($reference)
            ->setTransactionDesc($description);

        return $this->initiateSTKPush();
    }

    /**
     * Query the status of an STK Push transaction
     * 
     * @param string $checkoutRequestId The checkout request ID from the STK Push response
     * @return array The query response
     * @throws \RuntimeException When the request fails
     */
    public function stkQuery(string $checkoutRequestId): array
    {
        try {
            if ($this->config->getEnvironment() === 'sandbox') {
                $timestamp = "20240918055823";
            } elseif ($this->config->getEnvironment() === 'production') {
                $timestamp = date('YmdHis');
            } else {
                throw new \InvalidArgumentException('Invalid environment');
            }

            $shortcode = $this->config->getShortcode();
            $password = base64_encode(hash('sha256', $shortcode . $this->config->getPasskey() . $timestamp));

            $response = $this->client->request(
                'POST',
                $this->config->getBaseUrl() . "/mpesa/stkpushquery/v1/query",
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->auth->getToken(),
                    ],
                    'json' => [
                        "BusinessShortCode" => $shortcode,
                        "Password" => $password,
                        "Timestamp" => $timestamp,
                        "CheckoutRequestID" => $checkoutRequestId
                    ]
                ]
            );

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            throw new \RuntimeException('STK Push query failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Register URLs for receiving M-Pesa transaction notifications
     * 
     * @param string $confirmationUrl URL for confirmation notifications
     * @param string $validationUrl URL for validation notifications
     * @return array The registration response
     * @throws \RuntimeException When the request fails
     */
    public function registerUrls(string $confirmationUrl, string $validationUrl): array
    {
        try {
            $response = $this->client->request(
                'POST',
                $this->config->getBaseUrl() . "/mpesa/c2b/v1/registerurl",
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->auth->getToken(),
                    ],
                    'json' => [
                        "ShortCode" => $this->config->getShortcode(),
                        "ResponseType" => "Completed",
                        "ConfirmationURL" => $confirmationUrl,
                        "ValidationURL" => $validationUrl
                    ]
                ]
            );

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            throw new \RuntimeException('URL registration failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Set the customer's phone number
     * 
     * @param string $phone Phone number in the format 2517XXXXXXXX
     * @return self Returns the current instance for method chaining
     * @throws \InvalidArgumentException If phone number format is invalid
     */
    public function setPhoneNumber(string $phone): self
    {
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
    public function setAmount(float $amount): self
    {
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
    public function setCallbackUrl(string $url): self
    {
        if (!filter_var($url, FILTER_VALIDATE_URL) || strpos($url, 'https://') !== 0) {
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
    public function setTransactionDesc(string $desc): self
    {
        $this->transactionDesc = $desc;
        return $this;
    }

    /**
     * Set the account reference
     * 
     * @param string $reference Reference for the transaction
     * @return self Returns the current instance for method chaining
     */
    public function setAccountReference(string $reference): self
    {
        $this->accountReference = $reference;
        return $this;
    }

    /**
     * Set the SSL verification flag
     * 
     * @param bool $verify SSL verification flag
     * @return self Returns the current instance for method chaining
     */
    public function setVerifySSL(bool $verify): self
    {
        $this->auth->setVerifySSL($verify);
        return $this;
    }

    /**
     * Get the SSL verification flag
     * 
     * @return bool Returns true if SSL certificates are verified, false otherwise
     */
    public function getVerifySSL(): self
    {
        $this->auth->getVerifySSL();
        return $this;
    }

    /**
     * Set the business shortcode
     * 
     * @param string $shortcode The business shortcode to set
     * @return self Returns the current instance for method chaining
     */
    public function setShortcode(string $shortcode): self
    {
        $this->config->setShortcode($shortcode);
        return $this;
    }

    /**
     * Get the business shortcode
     * 
     * @return string The configured shortcode
     */
    public function getShortcode(): string
    {
        return $this->config->getShortcode();
    }

    /**
     * Set the HTTP client instance
     * 
     * @param Client $client GuzzleHttp client instance
     * @return self Returns the current instance for method chaining
     */
    public function setClient(Client $client): self
    {
        $this->client = $client;
        $this->auth->setClient($client);
        return $this;
    }

    /**
     * Set the initiator name for B2C transactions
     * 
     * @param string $initiatorName The name of the initiator
     * @return self Returns the current instance for method chaining
     */
    public function setInitiatorName(string $initiatorName): self
    {
        $this->initiatorName = $initiatorName;
        return $this;
    }
}