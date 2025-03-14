<?php

namespace MesaSDK\PhpMpesa\Traits;

use MesaSDK\PhpMpesa\MpesaException;
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
    protected ?string $queueTimeOutURL = null;

    /**
     * URL for result notifications
     * @var string|null
     */
    protected ?string $resultURL = null;

    /**
     * Additional information about the transaction
     * @var string|null
     */
    protected ?string $occasion = null;

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
     * @return self
     */
    public function setSecurityCredential(string $securityCredential): self
    {
        $this->securityCredential = $securityCredential;
        return $this;
    }

    /**
     * Set the command ID
     *
     * @param string $commandId
     * @return self
     * @throws MpesaException
     */
    public function setCommandID(string $commandId): self
    {
        $this->validateB2CParameters($commandId);
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

    public function getAmount(): float
    {
        return $this->getAmount();
    }

    /**
     * Set the organization's shortcode (PartyA)
     *
     * @param string $partyA
     * @return self
     */
    public function setPartyA(string $partyA): self
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
     * @param string $queueTimeOutURL
     * @return self
     */
    public function setQueueTimeOutURL(string $queueTimeOutURL): self
    {
        $this->queueTimeOutURL = $queueTimeOutURL;
        return $this;
    }

    /**
     * Set the result URL
     *
     * @param string $resultURL
     * @return self
     */
    public function setResultURL(string $resultURL): self
    {
        $this->resultURL = $resultURL;
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
            'QueueTimeOutURL' => $this->queueTimeOutURL,
            'ResultURL' => $this->resultURL,
            'Occasion' => $this->occasion
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
            'occasion' => 'Occasion'
        ];

        foreach ($requiredFields as $property => $fieldName) {
            if (empty($this->$property)) {
                throw new MpesaException("The {$fieldName} field is required for B2C transaction", 400);
            }
        }
    }

    /**
     * Initiates a Business to Customer (B2C) payment
     *
     * @param string $initiatorName The name of the initiator initiating the request
     * @param string $securityCredential The encrypted security credential
     * @param string $commandId The type of B2C transaction (BusinessPayment, SalaryPayment, PromotionPayment)
     * @param float $amount The amount to be sent to the customer
     * @param string $partyA The organization's shortcode
     * @param string $partyB The customer's phone number
     * @param string $remarks Additional information about the transaction
     * @param string $occasion Additional information about the transaction
     * @param string|null $queueTimeOutURL URL for timeout notifications
     * @param string|null $resultURL URL for result notifications
     * @return MpesaResponse The API response
     * @throws MpesaException
     */
    public function b2c(
        string $initiatorName,
        string $securityCredential,
        string $commandId,
        float $amount,
        string $partyA,
        string $partyB,
        string $remarks,
        string $occasion,
        ?string $queueTimeOutURL = null,
        ?string $resultURL = null
    ): MpesaResponse {
        return $this->setInitiatorName($initiatorName)
            ->setSecurityCredential($securityCredential)
            ->setCommandID($commandId)
            ->setAmount($amount)
            ->setPartyA($partyA)
            ->setPartyB($partyB)
            ->setRemarks($remarks)
            ->setOccasion($occasion)
            ->when($queueTimeOutURL, fn($self) => $self->setQueueTimeOutURL($queueTimeOutURL))
            ->when($resultURL, fn($self) => $self->setResultURL($resultURL))
            ->send();
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
     * Validates the B2C command ID
     *
     * @param string $commandId The command ID to validate
     * @throws MpesaException
     */
    private function validateB2CParameters(string $commandId): void
    {
        $validCommandIds = ['BusinessPayment', 'SalaryPayment', 'PromotionPayment'];

        if (!in_array($commandId, $validCommandIds)) {
            throw new MpesaException(
                'Invalid CommandID. Must be one of: ' . implode(', ', $validCommandIds),
                400
            );
        }
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
}