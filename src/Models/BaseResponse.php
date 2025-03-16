<?php

namespace MesaSDK\PhpMpesa\Models;

abstract class BaseResponse implements \JsonSerializable
{
    /** @var string|null Response code from M-Pesa API */
    protected ?string $responseCode = null;

    /** @var string|null Response description from M-Pesa API */
    protected ?string $responseDescription = null;

    /** @var string|null Conversation ID from M-Pesa API */
    protected ?string $conversationId = null;

    /** @var string|null Originator conversation ID from M-Pesa API */
    protected ?string $originatorConversationId = null;

    /**
     * Create a new response instance from API response array
     * 
     * @param array $response The API response data
     * @return self
     */
    public static function fromArray(array $response): self
    {
        $instance = new static();

        $instance->responseCode = $response['ResponseCode'] ?? null;
        $instance->responseDescription = $response['ResponseDescription'] ?? null;
        $instance->conversationId = $response['ConversationID'] ?? null;
        $instance->originatorConversationId = $response['OriginatorConversationID'] ?? null;

        return $instance;
    }

    /**
     * Check if the response indicates success
     * 
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return $this->responseCode === '0';
    }

    /**
     * Get the response code
     * 
     * @return string|null
     */
    public function getResponseCode(): ?string
    {
        return $this->responseCode;
    }

    /**
     * Get the response description
     * 
     * @return string|null
     */
    public function getResponseDescription(): ?string
    {
        return $this->responseDescription;
    }

    /**
     * Get the conversation ID
     * 
     * @return string|null
     */
    public function getConversationId(): ?string
    {
        return $this->conversationId;
    }

    /**
     * Get the originator conversation ID
     * 
     * @return string|null
     */
    public function getOriginatorConversationId(): ?string
    {
        return $this->originatorConversationId;
    }

    /**
     * Convert the response to an array
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'ResponseCode' => $this->responseCode,
            'ResponseDescription' => $this->responseDescription,
            'ConversationID' => $this->conversationId,
            'OriginatorConversationID' => $this->originatorConversationId
        ];
    }

    /**
     * Specify data which should be serialized to JSON
     * 
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}