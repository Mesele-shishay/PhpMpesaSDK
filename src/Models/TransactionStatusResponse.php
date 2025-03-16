<?php

namespace MesaSDK\PhpMpesa\Models;

class TransactionStatusResponse extends BaseResponse
{
    /** @var string|null The result type */
    protected ?string $resultType = null;

    /** @var string|null The result code */
    protected ?string $resultCode = null;

    /** @var string|null The result description */
    protected ?string $resultDesc = null;

    /** @var string|null The transaction status */
    protected ?string $transactionStatus = null;

    /** @var float|null The transaction amount */
    protected ?float $amount = null;

    /** @var string|null The transaction date and time */
    protected ?string $transactionDate = null;

    /** @var string|null The phone number involved in the transaction */
    protected ?string $phoneNumber = null;

    /** @var string|null The debit party name */
    protected ?string $debitPartyName = null;

    /** @var string|null The credit party name */
    protected ?string $creditPartyName = null;

    /** @var string|null The transaction receipt number */
    protected ?string $receiptNumber = null;

    /** @var array The raw result parameters */
    protected array $rawResultParameters = [];

    /**
     * Create a new transaction status response instance from API response array
     * 
     * @param array $response The API response data
     * @return static
     */
    public static function fromArray(array $response): self
    {
        $instance = new static();

        // Set base properties from parent
        $parentInstance = parent::fromArray($response);
        $instance->responseCode = $parentInstance->responseCode;
        $instance->responseDescription = $parentInstance->responseDescription;
        $instance->conversationId = $parentInstance->conversationId;
        $instance->originatorConversationId = $parentInstance->originatorConversationId;

        // Initialize raw parameters
        $instance->rawResultParameters = [];

        if (isset($response['Result'])) {
            $result = $response['Result'];

            // Set basic result data
            $instance->resultType = $result['ResultType'] ?? null;
            $instance->resultCode = $result['ResultCode'] ?? null;
            $instance->resultDesc = $result['ResultDesc'] ?? null;

            // Store raw result parameters
            $instance->rawResultParameters = $result['ResultParameters']['ResultParameter'] ?? [];

            // Process result parameters
            if (!empty($instance->rawResultParameters)) {
                foreach ($instance->rawResultParameters as $param) {
                    if (!isset($param['Key'], $param['Value']))
                        continue;

                    switch ($param['Key']) {
                        case 'TransactionStatus':
                            $instance->transactionStatus = $param['Value'];
                            break;
                        case 'Amount':
                            $instance->amount = (float) $param['Value'];
                            break;
                        case 'FinalisedTime':
                            $instance->transactionDate = $param['Value'];
                            break;
                        case 'PhoneNumber':
                            $instance->phoneNumber = $param['Value'];
                            break;
                        case 'DebitPartyName':
                            $instance->debitPartyName = $param['Value'];
                            break;
                        case 'CreditPartyName':
                            $instance->creditPartyName = $param['Value'];
                            break;
                        case 'ReceiptNo':
                            $instance->receiptNumber = $param['Value'];
                            break;
                    }
                }
            }
        }

        return $instance;
    }

    /**
     * Get the result code
     * 
     * @return string|null
     */
    public function getResultCode(): ?string
    {
        return $this->resultCode;
    }

    /**
     * Get the result description
     * 
     * @return string|null
     */
    public function getResultDesc(): ?string
    {
        return $this->resultDesc;
    }

    /**
     * Get the result type
     * 
     * @return string|null
     */
    public function getResultType(): ?string
    {
        return $this->resultType;
    }

    /**
     * Get the transaction status
     * 
     * @return string|null
     */
    public function getTransactionStatus(): ?string
    {
        return $this->transactionStatus;
    }

    /**
     * Get the transaction amount
     * 
     * @return float|null
     */
    public function getAmount(): ?float
    {
        return $this->amount;
    }

    /**
     * Get the transaction date
     * 
     * @return string|null
     */
    public function getTransactionDate(): ?string
    {
        return $this->transactionDate;
    }

    /**
     * Get the phone number
     * 
     * @return string|null
     */
    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    /**
     * Get the debit party name
     * 
     * @return string|null
     */
    public function getDebitPartyName(): ?string
    {
        return $this->debitPartyName;
    }

    /**
     * Get the credit party name
     * 
     * @return string|null
     */
    public function getCreditPartyName(): ?string
    {
        return $this->creditPartyName;
    }

    /**
     * Get the receipt number
     * 
     * @return string|null
     */
    public function getReceiptNumber(): ?string
    {
        return $this->receiptNumber;
    }

    /**
     * Get all raw result parameters
     * 
     * @return array
     */
    public function getRawResultParameters(): array
    {
        return $this->rawResultParameters;
    }

    /**
     * Get a specific result parameter by key
     * 
     * @param string $key The parameter key to look for
     * @param mixed $default Default value if key not found
     * @return mixed
     */
    public function getResultParameter(string $key, $default = null)
    {
        foreach ($this->rawResultParameters as $param) {
            if ($param['Key'] === $key) {
                return $param['Value'];
            }
        }
        return $default;
    }

    /**
     * Check if the transaction is completed
     * 
     * @return bool
     */
    public function isCompleted(): bool
    {
        return $this->transactionStatus === 'Completed';
    }

    /**
     * Convert the response to an array
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'resultType' => $this->resultType,
            'resultCode' => $this->resultCode,
            'resultDesc' => $this->resultDesc,
            'transactionStatus' => $this->transactionStatus,
            'amount' => $this->amount,
            'transactionDate' => $this->transactionDate,
            'phoneNumber' => $this->phoneNumber,
            'debitPartyName' => $this->debitPartyName,
            'creditPartyName' => $this->creditPartyName,
            'receiptNumber' => $this->receiptNumber,
            'rawResultParameters' => $this->rawResultParameters
        ];
    }

    /**
     * Convert the response to JSON
     * 
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }
}