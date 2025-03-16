<?php

namespace MesaSDK\PhpMpesa\Traits;

use MesaSDK\PhpMpesa\Exceptions\MpesaException;
use MesaSDK\PhpMpesa\Responses\MpesaResponse;
use MesaSDK\PhpMpesa\Models\TransactionStatusResponse;

trait TransactionStatusTrait
{
    /** @var string|null The transaction ID to query */
    protected ?string $transactionId = null;

    /** @var string|null The original conversation ID to query */
    protected ?string $originalConversationId = null;

    /** @var string|null The initiator name */
    protected ?string $statusInitiator = null;

    /** @var string|null The security credential */
    protected ?string $statusSecurityCredential = null;

    /** @var string|null The identifier type */
    protected ?string $identifierType = '4';

    /** @var string|null The remarks */
    protected ?string $statusRemarks = 'Transaction Status Query';

    /** @var string|null The occasion */
    protected ?string $statusOccasion = null;

    /**
     * Set the transaction ID for status query
     * 
     * @param string $transactionId The M-Pesa transaction ID
     * @return self
     */
    public function setTransactionId(string $transactionId): self
    {
        $this->transactionId = $transactionId;
        return $this;
    }

    /**
     * Set the original conversation ID for status query
     * 
     * @param string $conversationId The original conversation ID
     * @return self
     */
    public function setOriginalConversationId(string $conversationId): self
    {
        $this->originalConversationId = $conversationId;
        return $this;
    }

    /**
     * Set the initiator name for status query
     * 
     * @param string $initiator The initiator name
     * @return self
     */
    public function setStatusInitiator(string $initiator): self
    {
        $this->statusInitiator = $initiator;
        return $this;
    }

    /**
     * Set the security credential for status query
     * 
     * @param string $credential The encrypted security credential
     * @return self
     */
    public function setStatusSecurityCredential(string $credential): self
    {
        $this->statusSecurityCredential = $credential;
        return $this;
    }

    /**
     * Set the identifier type for status query
     * 
     * @param string $type The identifier type (default: '4' for organization shortcode)
     * @return self
     */
    public function setIdentifierType(string $type): self
    {
        $this->identifierType = $type;
        return $this;
    }

    /**
     * Set remarks for status query
     * 
     * @param string $remarks The remarks
     * @return self
     */
    public function setStatusRemarks(string $remarks): self
    {
        $this->statusRemarks = $remarks;
        return $this;
    }

    /**
     * Set occasion for status query
     * 
     * @param string $occasion The occasion
     * @return self
     */
    public function setStatusOccasion(string $occasion): self
    {
        $this->statusOccasion = $occasion;
        return $this;
    }

    /**
     * Execute the transaction status query
     * 
     * @return TransactionStatusResponse
     * @throws MpesaException
     */
    public function queryTransactionStatus(): TransactionStatusResponse
    {
        try {
            // Validate required fields
            if (empty($this->statusInitiator)) {
                throw new MpesaException('Initiator is required for transaction status query');
            }
            if (empty($this->statusSecurityCredential)) {
                throw new MpesaException('Security credential is required for transaction status query');
            }
            if (
                ($this->transactionId === null || $this->transactionId === '') &&
                ($this->originalConversationId === null || $this->originalConversationId === '')
            ) {
                throw new MpesaException('Either TransactionID or OriginalConversationID is required');
            }
            if (empty($this->resultUrl)) {
                throw new MpesaException('Result URL is required for transaction status query');
            }
            if (empty($this->timeoutUrl)) {
                throw new MpesaException('Timeout URL is required for transaction status query');
            }

            $data = [
                'Initiator' => $this->statusInitiator,
                'SecurityCredential' => $this->statusSecurityCredential,
                'CommandID' => 'TransactionStatusQuery',
                'TransactionID' => $this->transactionId ?? '0',
                'OriginalConversationID' => $this->originalConversationId ?? '',
                'PartyA' => $this->config->getShortcode(),
                'IdentifierType' => $this->identifierType,
                'ResultURL' => $this->resultUrl,
                'QueueTimeOutURL' => $this->timeoutUrl,
                'Remarks' => $this->statusRemarks,
                'Occasion' => $this->statusOccasion ?? ''
            ];

            $endpoint = $this->config->getBaseUrl() . '/mpesa/transactionstatus/v1/query';

            $response = $this->client->post($endpoint, [
                'json' => $data,
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->auth->getToken(),
                    'Content-Type' => 'application/json'
                ]
            ]);

            $decodedResponse = json_decode($response->getBody(), true);

            if (!$decodedResponse) {
                throw new MpesaException('Failed to decode API response');
            }

            return TransactionStatusResponse::fromArray($decodedResponse);

        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            throw new MpesaException(
                'Transaction status query failed: Network or server error',
                null,
                ['errorMessage' => $e->getMessage(), 'errorCode' => $e->getCode()],
                $e->getCode(),
                $e
            );
        } catch (MpesaException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new MpesaException(
                'Transaction status query failed: ' . $e->getMessage(),
                null,
                ['errorMessage' => $e->getMessage(), 'errorCode' => $e->getCode()],
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Process the transaction status callback
     * 
     * @param array $callbackData The callback data received from M-Pesa
     * @return TransactionStatusResponse
     * @throws MpesaException
     */
    public function processTransactionStatusCallback(array $callbackData): TransactionStatusResponse
    {
        if (!isset($callbackData['Result'])) {
            throw new MpesaException('Invalid callback data: Missing Result object');
        }

        return TransactionStatusResponse::fromArray($callbackData);
    }
}