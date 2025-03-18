<?php

namespace MesaSDK\PhpMpesa\Traits;

use MesaSDK\PhpMpesa\Exceptions\MpesaException;
use MesaSDK\PhpMpesa\Responses\MpesaResponse;

trait B2CTrait
{
    /**
     * The name of the initiator initiating the request
     * @var string|null
     */
    protected ?string $initiatorName = null;

    /**
     * The encrypted security credential
     * @var string|null
     */
    protected ?string $securityCredential = null;

    /**
     * The type of B2C transaction
     * @var string
     */
    protected string $commandID = 'BusinessPayment';

    /**
     * The organization's shortcode
     * @var string|null
     */
    protected ?string $partyA = null;

    /**
     * The customer's phone number
     * @var string|null
     */
    protected ?string $partyB = null;

    /**
     * Additional information about the transaction
     * @var string|null
     */
    protected ?string $remarks = null;

    /**
     * URL for timeout notifications
     * @var string|null
     */
    protected ?string $queueTimeOutUrl = null;

    /**
     * URL for result notifications
     * @var string|null
     */
    protected ?string $resultUrl = null;

    /**
     * Additional information about the transaction
     * @var string|null
     */
    protected ?string $occasion = null;

    /**
     * Array of B2C result codes and their descriptions
     * @var array
     */
    private array $b2cResultCodes = [
        0 => 'Success',
        1 => 'Internal Server Error',
        2 => 'Unauthorized',
        3 => 'Invalid initiator name',
        4 => 'Invalid security credential',
        5 => 'Invalid command ID',
        6 => 'Invalid party A',
        7 => 'Invalid party B',
        8 => 'Invalid amount',
        9 => 'Invalid remarks',
        10 => 'Invalid occassion',
        11 => 'Invalid URL',
        12 => 'Invalid queue timeout URL',
        13 => 'Invalid result URL',
        14 => 'Invalid transaction type',
        15 => 'Duplicate transaction',
        16 => 'Insufficient balance',
        17 => 'Invalid phone number',
        18 => 'Unregistered phone number',
        19 => 'Inactive phone number',
        20 => 'Blocked phone number',
        21 => 'Transaction limit exceeded',
        22 => 'Daily limit exceeded',
        23 => 'Weekly limit exceeded',
        24 => 'Monthly limit exceeded',
        25 => 'Invalid transaction',
        26 => 'Transaction expired',
        27 => 'Transaction cancelled',
        28 => 'Transaction failed',
        29 => 'Request cancelled',
        30 => 'Request timeout',
        31 => 'Request not found',
        32 => 'System error',
        33 => 'Invalid request',
        34 => 'Invalid parameters',
        35 => 'Invalid response',
        36 => 'Invalid status',
    ];

    /**
     * Set the initiator name
     *
     * @param string $initiatorName
     * @return self
     */
    public function setInitiatorName(string $initiatorName): self
    {
        $this->initiatorName = $initiatorName;
        return $this;
    }

    /**
     * Set the security credential
     *
     * @param string $securityCredential
     * @return \MesaSDK\PhpMpesa\Base\BaseMpesa
     */
    public function setSecurityCredential(string $securityCredential): \MesaSDK\PhpMpesa\Base\BaseMpesa
    {
        $this->securityCredential = $securityCredential;
        return $this;
    }

    /**
     * Set the command ID
     *
     * @param string $commandId
     * @return \MesaSDK\PhpMpesa\Base\BaseMpesa
     * @throws MpesaException
     */
    public function setCommandID(string $commandId): \MesaSDK\PhpMpesa\Base\BaseMpesa
    {
        $validCommands = ['BusinessPayment', 'SalaryPayment', 'PromotionPayment'];
        if (!in_array($commandId, $validCommands)) {
            throw new MpesaException(
                'Invalid CommandID. Must be one of: ' . implode(', ', $validCommands),
                400
            );
        }
        $this->commandID = $commandId;
        return $this;
    }

    /**
     * Set the amount
     *
     * @param float $amount
     * @return self
     */
    public function setAmount(float $amount): self
    {
        parent::setAmount($amount);
        return $this;
    }

    /**
     * Get the amount
     *
     * @return float
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * Set the organization's shortcode (PartyA)
     *
     * @param string $partyA
     * @return \MesaSDK\PhpMpesa\Base\BaseMpesa
     */
    public function setPartyA(string $partyA): \MesaSDK\PhpMpesa\Base\BaseMpesa
    {
        $this->partyA = $partyA;
        return $this;
    }

    /**
     * Set the customer's phone number (PartyB)
     *
     * @param string $partyB
     * @return self
     */
    public function setPartyB(string $partyB): self
    {
        $this->partyB = $partyB;
        return $this;
    }

    /**
     * Set the remarks
     *
     * @param string $remarks
     * @return self
     */
    public function setRemarks(string $remarks): self
    {
        $this->remarks = $remarks;
        return $this;
    }

    /**
     * Set the occasion
     *
     * @param string $occasion
     * @return self
     */
    public function setOccasion(string $occasion): self
    {
        $this->occasion = $occasion;
        return $this;
    }

    /**
     * Set the queue timeout URL
     *
     * @param string|null $url
     * @return self
     */
    public function setQueueTimeOutUrl(?string $url): self
    {
        $this->timeoutUrl = $url;
        return $this;
    }

    /**
     * Set the result URL
     *
     * @param string|null $url
     * @return self
     */
    public function setResultUrl(?string $url): self
    {
        $this->resultUrl = $url;
        return $this;
    }

    /**
     * Get the current B2C transaction data
     *
     * @return array
     */
    public function getB2CData(): array
    {
        return [
            'InitiatorName' => $this->initiatorName,
            'SecurityCredential' => $this->securityCredential,
            'CommandID' => $this->commandID,
            'Amount' => $this->amount,
            'PartyA' => $this->partyA,
            'PartyB' => $this->partyB,
            'Remarks' => $this->remarks,
            'QueueTimeOutURL' => $this->timeoutUrl,
            'ResultURL' => $this->resultUrl,
            'Occassion' => $this->occasion
        ];
    }

    /**
     * Send the B2C payment request
     *
     * @return MpesaResponse
     * @throws MpesaException
     */
    public function send(): MpesaResponse
    {
        $this->validateB2CData();

        $data = array_merge($this->getB2CData(), [
            'OriginatorConversationID' => $this->generateOriginatorConversationId()
        ]);


        // Set default URLs if not provided
        $data['QueueTimeOutURL'] = $data['QueueTimeOutURL'] ?? $this->config->get('queue_timeout_url');
        $data['ResultURL'] = $data['ResultURL'] ?? $this->config->get('result_url');

        $endpoint = $this->config->getBaseUrl() . '/mpesa/b2c/v2/paymentrequest';

        $response = $this->client->post($endpoint, [
            'json' => $data,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->auth->getToken(),
                'Content-Type' => 'application/json'
            ]
        ]);


        $decodedResponse = json_decode($response->getBody(), true);

        if (!$decodedResponse) {
            return MpesaResponse::error('Failed to decode API response');
        }

        // Format the response for MpesaResponse
        $formattedResponse = [
            'header' => [
                'responseCode' => $response->getStatusCode(),
                'responseMessage' => $decodedResponse['ResponseDescription'] ?? 'Unknown response',
                'customerMessage' => $decodedResponse['CustomerMessage'] ?? ($decodedResponse['ResponseDescription'] ?? 'Unknown response'),
                'timestamp' => date('Y-m-d\TH:i:s.v')
            ],
            'data' => $decodedResponse
        ];

        return new MpesaResponse($formattedResponse);
    }

    /**
     * Validate that all required B2C data is set
     *
     * @throws MpesaException
     */
    private function validateB2CData(): void
    {
        $requiredFields = [
            'initiatorName' => 'InitiatorName',
            'securityCredential' => 'SecurityCredential',
            'commandID' => 'CommandID',
            'amount' => 'Amount',
            'partyA' => 'PartyA',
            'partyB' => 'PartyB',
            'remarks' => 'Remarks',
            'occasion' => 'Occassion',
            'resultUrl' => 'ResultURL',
            'timeoutUrl' => 'QueueTimeOutURL'
        ];

        foreach ($requiredFields as $property => $fieldName) {
            if (empty($this->$property)) {
                throw new MpesaException("The {$fieldName} field is required for B2C transaction", 400);
            }
        }

        // Validate URLs
        if (!filter_var($this->resultUrl, FILTER_VALIDATE_URL) || strpos($this->resultUrl, 'https://') !== 0) {
            throw new MpesaException('Result URL must be a valid HTTPS URL', 400);
        }

        if (!filter_var($this->timeoutUrl, FILTER_VALIDATE_URL) || strpos($this->timeoutUrl, 'https://') !== 0) {
            throw new MpesaException('Queue Timeout URL must be a valid HTTPS URL', 400);
        }
    }

    /**
     * Initiates a Business to Customer (B2C) payment
     *
     * @param string|null $initiatorName The name of the initiator initiating the request
     * @param string|null $securityCredential The encrypted security credential
     * @param string|null $commandId The type of B2C transaction (BusinessPayment, SalaryPayment, PromotionPayment)
     * @param float|null $amount The amount to be sent to the customer
     * @param string|null $partyA The organization's shortcode
     * @param string|null $partyB The customer's phone number
     * @param string|null $remarks Additional information about the transaction
     * @param string|null $occasion Additional information about the transaction
     * @param string|null $queueTimeoutUrl URL for timeout notifications
     * @param string|null $resultUrl URL for result notifications
     * @return \MesaSDK\PhpMpesa\Responses\MpesaResponse The API response
     * @throws MpesaException
     */
    public function b2c(
        ?string $initiatorName = null,
        ?string $securityCredential = null,
        ?string $commandId = null,
        ?float $amount = null,
        ?string $partyA = null,
        ?string $partyB = null,
        ?string $remarks = null,
        ?string $occasion = null,
        ?string $queueTimeoutUrl = null,
        ?string $resultUrl = null
    ): MpesaResponse {
        // If parameters are provided directly, set them
        if ($initiatorName !== null) {
            $this->setInitiatorName($initiatorName);
        }
        if ($securityCredential !== null) {
            $this->setSecurityCredential($securityCredential);
        }
        if ($commandId !== null) {
            $this->setCommandID($commandId);
        }
        if ($amount !== null) {
            $this->setAmount($amount);
        }
        if ($partyA !== null) {
            $this->setPartyA($partyA);
        }
        if ($partyB !== null) {
            $this->setPartyB($partyB);
        }
        if ($remarks !== null) {
            $this->setRemarks($remarks);
        }
        if ($occasion !== null) {
            $this->setOccasion($occasion);
        }
        if ($queueTimeoutUrl !== null) {
            $this->setQueueTimeOutUrl($queueTimeoutUrl);
        }
        if ($resultUrl !== null) {
            $this->setResultUrl($resultUrl);
        }

        // Validate and send the request
        return $this->send();
    }

    /**
     * Conditionally execute a callback
     *
     * @param mixed $value
     * @param callable $callback
     * @return self
     */
    private function when($value, callable $callback): self
    {
        if (!empty($value)) {
            return $callback($this);
        }
        return $this;
    }

    /**
     * Generates a unique originator conversation ID
     *
     * @return string
     */
    private function generateOriginatorConversationId(): string
    {
        return uniqid('MPESA-B2C-', true);
    }

    /**
     * Process the B2C callback response
     * 
     * @param array|null $callbackData The callback data received from M-Pesa
     * @return self Returns the current instance for method chaining
     * @throws \InvalidArgumentException When callback data is null or invalid
     */
    public function processB2CCallback(?array $callbackData = null): self
    {
        if ($callbackData === null) {
            // Get the raw POST data if no data was passed
            $rawData = file_get_contents('php://input');
            if (empty($rawData)) {
                throw new \InvalidArgumentException('No callback data received');
            }

            $callbackData = json_decode($rawData, true);
            if (!is_array($callbackData)) {
                throw new \InvalidArgumentException('Invalid callback data format');
            }
        }

        $this->response = $callbackData['Result'] ?? [];
        return $this;
    }

    /**
     * Get the Transaction ID from the B2C callback
     * 
     * @return string|null The Transaction ID or null if not available
     */
    public function getTransactionId(): ?string
    {
        return $this->response['TransactionID'] ?? null;
    }

    /**
     * Get the Conversation ID from the B2C callback
     * 
     * @return string|null The Conversation ID or null if not available
     */
    public function getConversationId(): ?string
    {
        return $this->response['ConversationID'] ?? null;
    }

    /**
     * Get the Originator Conversation ID from the B2C callback
     * 
     * @return string|null The Originator Conversation ID or null if not available
     */
    public function getOriginatorConversationId(): ?string
    {
        return $this->response['OriginatorConversationID'] ?? null;
    }

    /**
     * Get all result parameters from the B2C callback
     * 
     * @return array The result parameters or empty array if not available
     */
    public function getResultParameters(): array
    {
        if (!isset($this->response['ResultParameters']['ResultParameter'])) {
            return [];
        }

        $params = [];
        foreach ($this->response['ResultParameters']['ResultParameter'] as $param) {
            $params[$param['Key']] = $param['Value'];
        }
        return $params;
    }

    /**
     * Get the transaction amount from the B2C callback
     * 
     * @return float|null The transaction amount or null if not available
     */
    public function getTransactionAmount(): ?float
    {
        $params = $this->getResultParameters();
        return isset($params['TransactionAmount']) ? (float) $params['TransactionAmount'] : null;
    }

    /**
     * Get the transaction receipt number from the B2C callback
     * 
     * @return string|null The transaction receipt number or null if not available
     */
    public function getTransactionReceipt(): ?string
    {
        $params = $this->getResultParameters();
        return $params['TransactionReceipt'] ?? null;
    }

    /**
     * Get the receiver's name from the B2C callback
     * 
     * @return string|null The receiver's name or null if not available
     */
    public function getReceiverName(): ?string
    {
        $params = $this->getResultParameters();
        return $params['ReceiverPartyPublicName'] ?? null;
    }

    /**
     * Get the transaction completion date and time
     * 
     * @return string|null The transaction completion date and time or null if not available
     */
    public function getTransactionDateTime(): ?string
    {
        $params = $this->getResultParameters();
        return $params['TransactionCompletedDateTime'] ?? null;
    }

    /**
     * Get all available account balances
     * 
     * @return array Array containing utility, working, and charges account balances
     */
    public function getAccountBalances(): array
    {
        $params = $this->getResultParameters();
        return [
            'utility' => isset($params['B2CUtilityAccountAvailableFunds']) ?
                (float) $params['B2CUtilityAccountAvailableFunds'] : null,
            'working' => isset($params['B2CWorkingAccountAvailableFunds']) ?
                (float) $params['B2CWorkingAccountAvailableFunds'] : null,
            'charges' => isset($params['B2CChargesPaidAccountAvailableFunds']) ?
                (float) $params['B2CChargesPaidAccountAvailableFunds'] : null
        ];
    }

    /**
     * Check if the recipient is a registered M-Pesa customer
     * 
     * @return bool|null True if recipient is registered, false if not, null if information not available
     */
    public function isRecipientRegistered(): ?bool
    {
        $params = $this->getResultParameters();
        return isset($params['B2CRecipientIsRegisteredCustomer']) ?
            $params['B2CRecipientIsRegisteredCustomer'] === 'Y' : null;
    }

    /**
     * Get detailed error message for a result code
     * 
     * @return string Detailed error message
     */
    public function getDetailedError(): string
    {
        $resultCode = $this->getResultCode();
        $resultDesc = $this->getResultDesc();

        if ($resultCode === null) {
            return 'Unknown error occurred';
        }

        $defaultMessage = $this->b2cResultCodes[$resultCode] ?? 'Unknown error code';
        return sprintf(
            'Error Code: %d - %s. %s',
            $resultCode,
            $defaultMessage,
            $resultDesc ? "Details: $resultDesc" : ''
        );
    }

    /**
     * Check if there was a specific type of error
     * 
     * @param int $errorCode The error code to check for
     * @return bool True if the error matches
     */
    public function hasError(int $errorCode): bool
    {
        return $this->getResultCode() === $errorCode;
    }

    /**
     * Check if the error is related to the recipient's phone number
     * 
     * @return bool True if the error is phone number related
     */
    public function hasPhoneNumberError(): bool
    {
        $phoneErrors = [17, 18, 19, 20]; // Phone number related error codes
        return in_array($this->getResultCode(), $phoneErrors);
    }

    /**
     * Check if the error is related to transaction limits
     * 
     * @return bool True if the error is limit related
     */
    public function hasLimitError(): bool
    {
        $limitErrors = [21, 22, 23, 24]; // Transaction limit related error codes
        return in_array($this->getResultCode(), $limitErrors);
    }

    /**
     * Check if the error is related to insufficient balance
     * 
     * @return bool True if there was insufficient balance
     */
    public function hasInsufficientBalanceError(): bool
    {
        return $this->hasError(16);
    }

    /**
     * Check if the error is related to invalid credentials
     * 
     * @return bool True if there was an authentication/credential error
     */
    public function hasCredentialError(): bool
    {
        $credentialErrors = [2, 3, 4]; // Authentication related error codes
        return in_array($this->getResultCode(), $credentialErrors);
    }

    /**
     * Check if the error is related to invalid parameters
     * 
     * @return bool True if there was a parameter validation error
     */
    public function hasValidationError(): bool
    {
        $validationErrors = [5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 34];
        return in_array($this->getResultCode(), $validationErrors);
    }

    /**
     * Check if the error is a system error
     * 
     * @return bool True if there was a system error
     */
    public function hasSystemError(): bool
    {
        $systemErrors = [1, 32, 35, 36];
        return in_array($this->getResultCode(), $systemErrors);
    }

    /**
     * Get a user-friendly error message
     * 
     * @return string User-friendly error message
     */
    public function getUserFriendlyError(): string
    {
        if ($this->hasPhoneNumberError()) {
            return 'There was an issue with the recipient\'s phone number. Please verify the number and try again.';
        }

        if ($this->hasLimitError()) {
            return 'Transaction limit exceeded. Please try a lower amount or try again later.';
        }

        if ($this->hasInsufficientBalanceError()) {
            return 'Insufficient balance to complete the transaction.';
        }

        if ($this->hasCredentialError()) {
            return 'Authentication failed. Please contact support.';
        }

        if ($this->hasValidationError()) {
            return 'Invalid transaction details provided. Please verify all information and try again.';
        }

        if ($this->hasSystemError()) {
            return 'A system error occurred. Please try again later or contact support if the problem persists.';
        }

        return 'An error occurred while processing the transaction. Please try again later.';
    }
}