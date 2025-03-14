<?php
namespace MesaSDK\PhpMpesa;

use GuzzleHttp\Client;
use MesaSDK\PhpMpesa\Traits\MpesaRegisterUrlTrait;
use MesaSDK\PhpMpesa\Traits\STKPushTrait;
use MesaSDK\PhpMpesa\Base\BaseMpesa;
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

    use STKPushTrait, MpesaRegisterUrlTrait;

    private Client $client;
    private ?STKPushTrait $stkPushService = null;

    /** @var string Customer's phone number */
    protected string $phoneNumber = '';

    /**
     * Mpesa constructor.
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


        $this->client = new Client(['verify' => $this->config->getVerifySSL()]);

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
     * Get the STK Push service instance
     * 
     * @return STKPushTrait
     */
    public function stkPush(): STKPushTrait
    {
        return $this->stkPushService;
    }

    /**
     * Configure the Mpesa instance directly
     * 
     * @param array $config Configuration values
     * @return self Returns the current instance for method chaining
     */
    public function configure(array $config): self
    {
        $this->config = $this->createConfigFromArray($config);
        $this->auth = new Authentication($this->config);
        $this->stkPushService = null; // Reset service instances
        return $this;
    }

    /**
     * Authenticate with the M-Pesa API
     * 
     * @return self Returns the current instance for method chaining
     * @throws \RuntimeException When authentication fails
     */
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
}