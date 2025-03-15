<?php

namespace MesaSDK\PhpMpesa\Traits;

trait CommonTrait
{
    /**
     * Get the result code from the response
     * 
     * @return string|null
     */
    public function getResultCode(): ?string
    {
        return $this->response['ResultCode'] ?? null;
    }

    /**
     * Get the result description from the response
     * 
     * @return string|null
     */
    function getResultDesc(): ?string
    {
        return $this->response['ResultDesc'] ?? null;
    }

    /**
     * Get the callback data from the response
     * 
     * @return array|null
     */
    public function getCallbackData(): ?array
    {
        return $this->response['CallbackMetadata']['Item'] ?? null;
    }

    /**
     * Check if the response was successful
     * 
     * @return bool
     */
    public function isSuccessful(): bool
    {

        return $this->getResultCode() == 0;
    }
}