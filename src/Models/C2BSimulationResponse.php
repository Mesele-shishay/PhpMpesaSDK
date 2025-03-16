<?php

namespace MesaSDK\PhpMpesa\Models;

class C2BSimulationResponse extends BaseResponse
{
    /** @var string|null Customer message from M-Pesa API */
    protected ?string $customerMessage = null;

    /** @var string|null Merchant request ID from M-Pesa API */
    protected ?string $merchantRequestId = null;

    /** @var string|null Checkout request ID from M-Pesa API */
    protected ?string $checkoutRequestId = null;

    /**
     * Create a new C2B simulation response instance from API response array
     * 
     * @param array $response The API response data
     * @return static
     */
    public static function fromArray(array $response): self
    {
        $instance = parent::fromArray($response);

        $instance->customerMessage = $response['CustomerMessage'] ?? null;
        $instance->merchantRequestId = $response['MerchantRequestID'] ?? null;
        $instance->checkoutRequestId = $response['CheckoutRequestID'] ?? null;

        return $instance;
    }

    /**
     * Get the customer message
     * 
     * @return string|null
     */
    public function getCustomerMessage(): ?string
    {
        return $this->customerMessage;
    }

    /**
     * Get the merchant request ID
     * 
     * @return string|null
     */
    public function getMerchantRequestId(): ?string
    {
        return $this->merchantRequestId;
    }

    /**
     * Get the checkout request ID
     * 
     * @return string|null
     */
    public function getCheckoutRequestId(): ?string
    {
        return $this->checkoutRequestId;
    }

    /**
     * Convert the response to an array
     * 
     * @return array
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'CustomerMessage' => $this->customerMessage,
            'MerchantRequestID' => $this->merchantRequestId,
            'CheckoutRequestID' => $this->checkoutRequestId
        ]);
    }
}