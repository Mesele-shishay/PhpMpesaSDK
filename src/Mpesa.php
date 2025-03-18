<?php
namespace MesaSDK\PhpMpesa;

use GuzzleHttp\Client;
use MesaSDK\PhpMpesa\Models\C2BSimulationResponse;
use MesaSDK\PhpMpesa\Traits\MpesaRegisterUrlTrait;
use MesaSDK\PhpMpesa\Traits\STKPushTrait;
use MesaSDK\PhpMpesa\Traits\C2BValidationTrait;
use MesaSDK\PhpMpesa\Traits\C2BSimulationTrait;
use MesaSDK\PhpMpesa\Base\BaseMpesa;
use MesaSDK\PhpMpesa\Traits\B2CTrait;
use MesaSDK\PhpMpesa\Traits\CommonTrait;
use MesaSDK\PhpMpesa\Traits\TransactionStatusTrait;
use MesaSDK\PhpMpesa\Traits\HasAccountBalance;
use MesaSDK\PhpMpesa\Contracts\MpesaInterface;
use MesaSDK\PhpMpesa\Exceptions;
use MesaSDK\PhpMpesa\Exceptions\MpesaException;
/**
 * Class Mpesa
 * 
 * Main class for interacting with the M-Pesa API, providing methods for
 * initiating transactions and handling M-Pesa payment operations.
 * 
 * This class provides a comprehensive interface to the M-Pesa payment gateway,
 * supporting operations such as:
 * - STK Push 251
 * - B2C Payments
 * - URL Registration
 * - Transaction Status Queries
 * - C2B Simulation
 * 
 * Example usage:
 * ```php
 * $mpesa = new Mpesa([
 *     'consumer_key' => 'your_consumer_key',
 *     'consumer_secret' => 'your_consumer_secret',
 *     'environment' => 'sandbox', // or 'production'
 *     'shortcode' => '174379',
 *     'key' => 'your_passkey'
 * ]);
 * 
 * // Initiate STK Push
 * $response = $mpesa->stkPush(
 *     100, // Amount
 *     '251712345678', // Phone number
 *     'REF123', // Reference
 *     'Payment for goods' // Description
 * );
 * ```
 * 
 * @package MpesaSDK
 * @see https://developer.safaricom.et/ for M-Pesa API documentation
 */
class Mpesa extends BaseMpesa
{
    use STKPushTrait,
    B2CTrait,
    C2BValidationTrait,
    C2BSimulationTrait,
    MpesaRegisterUrlTrait,
    CommonTrait,
    TransactionStatusTrait,
    HasAccountBalance {
        // Resolve method conflicts
        CommonTrait::getResultCode insteadof STKPushTrait, B2CTrait, TransactionStatusTrait;
        CommonTrait::getResultDesc insteadof STKPushTrait, B2CTrait, TransactionStatusTrait;
        CommonTrait::isSuccessful insteadof STKPushTrait, B2CTrait, TransactionStatusTrait;
        CommonTrait::getCallbackData insteadof STKPushTrait, B2CTrait, TransactionStatusTrait;
    }

    private Client $client;

    /** @var string Customer's phone number */
    protected string $phoneNumber = '';

    /** @var string|null The name of the initiator */
    protected ?string $initiatorName = null;

    /** @var string|null URL for timeout notifications */
    protected ?string $timeoutUrl = null;

    /** @var string|null URL for transaction results */
    protected ?string $resultUrl = null;

    /** @var string|null Transaction occasion */
    protected ?string $occasion = null;

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
     * Query the status of an STK Push transaction
     * 
     * @param string $checkoutRequestId The checkout request ID
     * @return array Response from the API
     * @throws MpesaException
     */
    public function querySTKStatus(string $checkoutRequestId): array
    {
        $payload = [
            'BusinessShortCode' => $this->config->getShortcode(),
            'Password' => $this->generatePassword(),
            'Timestamp' => $this->getTimestamp(),
            'CheckoutRequestID' => $checkoutRequestId
        ];

        return $this->executeRequest('POST', '/stkpushquery/v1/query', $payload);
    }

    /**
     * Register URLs for receiving transaction callbacks
     * 
     * @param string $confirmationUrl URL for confirmation notifications
     * @param string $validationUrl URL for validation notifications
     * @return array Response from the API
     * @throws MpesaException
     */
    public function registerUrls(string $confirmationUrl, string $validationUrl): array
    {
        $payload = [
            'ShortCode' => $this->config->getShortcode(),
            'ResponseType' => 'Completed',
            'ConfirmationURL' => $confirmationUrl,
            'CommandID' => 'RegisterURL',
            'ValidationURL' => $validationUrl
        ];

        print_r($payload);

        return $this->executeRequest('POST', '/v1/c2b-register-url/register?apikey=' . $this->getApiKey(), $payload);
    }

    /**
     * Send a B2C payment
     * 
     * @param array $payload The B2C payment payload
     * @return array Response from the API
     * @throws MpesaException
     */
    public function sendB2C(array $payload): array
    {
        if (empty($this->initiatorName)) {
            throw new MpesaException('Initiator name is required for B2C transactions');
        }

        if (empty($this->resultUrl)) {
            throw new MpesaException('Result URL is required for B2C transactions');
        }

        if (empty($this->timeoutUrl)) {
            throw new MpesaException('Timeout URL is required for B2C transactions');
        }

        $defaultPayload = [
            'InitiatorName' => $this->initiatorName,
            'SecurityCredential' => $this->securityCredential,
            'CommandID' => $this->commandID,
            'Amount' => $this->amount,
            'PartyA' => $this->config->getShortcode(),
            'PartyB' => $this->phoneNumber,
            'Remarks' => $this->transactionDesc,
            'QueueTimeOutURL' => $this->timeoutUrl,
            'ResultURL' => $this->resultUrl,
            'Occassion' => $this->occasion ?? ''
        ];

        $payload = array_merge($defaultPayload, $payload);

        return $this->executeRequest('POST', '/mpesa/b2c/v1/paymentrequest', $payload);
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
        // Basic validation for Ethiopian phone numbers
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
    public function getVerifySSL(): bool
    {
        return $this->auth->getVerifySSL();
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

    /**
     * Generate the M-Pesa API password
     * 
     * @return string
     */
    protected function generatePassword(): string
    {
        $timestamp = $this->getTimestamp();
        return base64_encode($this->config->getShortcode() . $this->config->getPasskey() . $timestamp);
    }

    /**
     * Get the current timestamp in YYYYMMDDHHmmss format
     * 
     * @return string
     */
    protected function getTimestamp(): string
    {
        return date('YmdHis');
    }

    /**
     * Set the timeout URL for B2C transactions
     * 
     * @param string $url Valid HTTPS URL
     * @return self Returns the current instance for method chaining
     * @throws \InvalidArgumentException If URL is invalid or not HTTPS
     */
    public function setTimeoutUrl(string $url): self
    {
        if (!filter_var($url, FILTER_VALIDATE_URL) || strpos($url, 'https://') !== 0) {
            throw new \InvalidArgumentException('Timeout URL must be a valid HTTPS URL');
        }
        $this->timeoutUrl = $url;
        return $this;
    }



    /**
     * Set the occasion for B2C transactions
     */
    public function setOccasion(string $occasion): self
    {
        $this->occasion = $occasion;
        return $this;
    }

    /**
     * Initiate an STK Push transaction
     * 
     * @param float $amount Amount to charge
     * @param string $phone Customer's phone number
     * @param string $reference Account reference
     * @param string $description Transaction description
     * @return array Response from the API
     * @throws MpesaException|\InvalidArgumentException
     */
    public function stkPush(float $amount, string $phone, string $reference, string $description): array
    {
        $this->setAmount($amount)
            ->setPhoneNumber($phone)
            ->setAccountReference($reference)
            ->setTransactionDesc($description);

        $this->ussdPush();
        return $this->response;
    }

    /**
     * Query the status of an STK Push transaction
     * 
     * @param string $checkoutRequestId The checkout request ID to query
     * @return array Response from the API
     * @throws MpesaException
     */
    public function stkQuery(string $checkoutRequestId): array
    {
        return $this->querySTKStatus($checkoutRequestId);
    }

    /**
     * Simulate a C2B payment transaction
     * 
     * @param float $amount Amount to be paid
     * @param string $phone Customer's phone number
     * @param string $billRef Bill reference number
     * @return C2BSimulationResponse Response from the API
     * @throws MpesaException
     * @deprecated Use fluent interface instead: setC2BAmount()->setC2BMsisdn()->setC2BBillRefNumber()->executeC2BSimulation()
     */
    public function simulateCustomerPayment(float $amount, string $phone, string $billRef): C2BSimulationResponse
    {
        return $this->simulateC2B(
            'CustomerPayBillOnline',
            $amount,
            $phone,
            $billRef,
            $this->config->getShortcode()
        );
    }

    /**
     * Execute C2B simulation with previously set parameters
     * 
     * @return C2BSimulationResponse
     * @throws MpesaException If required parameters are not set
     */
    public function executeC2BSimulation(): C2BSimulationResponse
    {
        return $this->simulateC2B(
            $this->c2bCommandId,
            $this->c2bAmount,
            $this->c2bMsisdn,
            $this->c2bBillRefNumber,
            $this->config->getShortcode()
        );
    }

    protected function getBearerToken(): string
    {
        return $this->auth->getToken();
    }

    protected function getQueueTimeoutUrl(): string
    {
        return $this->timeoutUrl ?? '';
    }

    protected function getResultUrl(): string
    {
        return $this->resultUrl ?? '';
    }

    protected function getSecurityCredential(): string
    {
        return $this->securityCredential ?? '';
    }

    protected function executeRequest(string $method, string $endpoint, array $payload): array
    {
        try {
            // Ensure we have a valid access token only if auto_authenticate is enabled
            if ($this->auth->isExpired() && $this->config->getAutoAuthenticate()) {
                $this->authenticate();
            }

            $response = $this->auth->makeRequest($method, $endpoint, $payload);

            // If response contains resultDesc, return just that for error cases
            if (isset($response['resultDesc']) && isset($response['resultCode']) && $response['resultCode'] !== '0') {
                return ['ResponseCode' => $response['resultCode'], 'ResponseDescription' => $response['resultDesc']];
            }

            return $response;
        } catch (\Exception $e) {
            throw new MpesaException($e->getMessage());
        }
    }

    public function setAccountBalanceInitiator(string $initiator): self
    {
        $this->accountBalanceInitiator = $initiator;
        return $this;
    }

    public function setAccountBalancePartyA(string $partyA): self
    {
        $this->accountBalancePartyA = $partyA;
        return $this;
    }

    public function setQueueTimeOutUrl(?string $url): BaseMpesa
    {
        $this->timeoutUrl = $url;
        return $this;
    }

    public function setResultUrl(?string $url): BaseMpesa
    {
        $this->resultUrl = $url;
        return $this;
    }

    protected function makeRequest(string $endpoint, array $payload): array
    {
        return $this->executeRequest('POST', $endpoint, $payload);
    }
}