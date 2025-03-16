<?php

namespace MesaSDK\PhpMpesa\Contracts;

interface MpesaInterface
{
    public function authenticate(): self;
    public function ussdPush(): self;
    public function setPhoneNumber(string $phone): self;
    public function setAmount(float $amount): self;
    public function setCallbackUrl(string $url): self;
    public function setTransactionDesc(string $desc): self;
    public function setAccountReference(string $reference): self;
    public function processCallback(array $callbackData): self;
    public function isSuccessful(): bool;
    public function getErrorMessage(): string;
    public function getMerchantRequestID(): ?string;
    public function getCheckoutRequestID(): ?string;
}