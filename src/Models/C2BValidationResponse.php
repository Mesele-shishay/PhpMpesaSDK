<?php

namespace MesaSDK\PhpMpesa\Models;

class C2BValidationResponse extends BaseResponse
{
    /** @var string|null Third party transaction ID */
    protected ?string $thirdPartyTransId = null;

    /** @var array Transaction details */
    protected array $transactionDetails = [];

    /**
     * Create a new C2B validation response instance from API response array
     * 
     * @param array $response The API response data
     * @return static
     */
    public static function fromArray(array $response): self
    {
        $instance = parent::fromArray($response);

        $instance->thirdPartyTransId = $response['ThirdPartyTransID'] ?? null;

        // Store all transaction details
        $instance->transactionDetails = array_filter($response, function ($key) {
            return !in_array($key, [
                'ResponseCode',
                'ResponseDescription',
                'ConversationID',
                'OriginatorConversationID',
                'ThirdPartyTransID'
            ]);
        }, ARRAY_FILTER_USE_KEY);

        return $instance;
    }

    /**
     * Get the third party transaction ID
     * 
     * @return string|null
     */
    public function getThirdPartyTransId(): ?string
    {
        return $this->thirdPartyTransId;
    }

    /**
     * Get all transaction details
     * 
     * @return array
     */
    public function getTransactionDetails(): array
    {
        return $this->transactionDetails;
    }

    /**
     * Get a specific transaction detail
     * 
     * @param string $key The detail key to get
     * @param mixed $default Default value if key doesn't exist
     * @return mixed
     */
    public function getTransactionDetail(string $key, $default = null)
    {
        return $this->transactionDetails[$key] ?? $default;
    }

    /**
     * Convert the response to an array
     * 
     * @return array
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'ThirdPartyTransID' => $this->thirdPartyTransId,
            'TransactionDetails' => $this->transactionDetails
        ]);
    }
}