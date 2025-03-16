<?php

namespace MesaSDK\PhpMpesa\Traits;

use MesaSDK\PhpMpesa\Exceptions\MpesaException;
use MesaSDK\PhpMpesa\Contracts\MpesaInterface;

trait STKPushTrait
{
    /**
     * Initiate an STK Push request
     * 
     * @return \MesaSDK\PhpMpesa\Contracts\MpesaInterface Returns the current instance for method chaining
     * @throws MpesaException When the request fails or response indicates an error
     */
    public function ussdPush(): MpesaInterface
    {
        $this->validateRequiredFields();

        try {
            if ($this->config->getEnvironment() === 'sandbox') {
                $timestamp = "20240918055823";
            } elseif ($this->config->getEnvironment() === 'production') {
                $timestamp = date('YmdHis');
            } else {
                throw new \InvalidArgumentException('Invalid environment');
            }

            $shortcode = $this->config->getShortcode();
            $password = $this->testPassword ?? base64_encode(hash('sha256', $shortcode . $this->config->getPasskey() . $timestamp));

            $payload = [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->auth->getToken(),
                ],
                'json' => [
                    "MerchantRequestID" => "Partner name-" . uniqid(),
                    "BusinessShortCode" => $shortcode,
                    "Password" => $password,
                    "Timestamp" => $timestamp,
                    "TransactionType" => "CustomerPayBillOnline",
                    "Amount" => $this->amount,
                    "PartyA" => $this->phoneNumber,
                    "PartyB" => $shortcode,
                    "PhoneNumber" => $this->phoneNumber,
                    "CallBackURL" => $this->callbackUrl,
                    "AccountReference" => $this->accountReference,
                    "TransactionDesc" => $this->transactionDesc,
                    "ReferenceData" => [
                        [
                            "Key" => "ThirdPartyReference",
                            "Value" => "Ref-" . uniqid()
                        ]
                    ]
                ]
            ];

            $response = $this->client->request(
                'POST',
                $this->config->getBaseUrl() . "/mpesa/stkpush/v3/processrequest",
                $payload
            );

            $this->response = json_decode($response->getBody(), true);

            if (isset($this->response['ResponseCode']) && $this->response['ResponseCode'] !== '0') {
                throw MpesaException::fromResponse($this->response);
            }

            return $this;
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            $this->response = [
                'errorMessage' => $e->getMessage(),
                'errorCode' => $e->getCode()
            ];
            throw new MpesaException(
                'STK Push request failed: Network or server error',
                null,
                $this->response,
                $e->getCode(),
                $e
            );
        } catch (MpesaException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->response = [
                'errorMessage' => $e->getMessage(),
                'errorCode' => $e->getCode()
            ];
            throw new MpesaException(
                'STK Push request failed: ' . $e->getMessage(),
                null,
                $this->response,
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Process the STK Push callback response
     * 
     * @param array $callbackData The callback data received from M-Pesa
     * @return \MesaSDK\PhpMpesa\Contracts\MpesaInterface Returns the current instance for method chaining
     */
    public function processCallback(array $callbackData): MpesaInterface
    {
        $this->response = $callbackData['Body']['stkCallback'] ?? [];
        return $this;
    }

    /**
     * Get the Result Code from the STK Push callback
     * 
     * @return int|null The Result Code or null if not available
     */
    public function getResultCode(): ?int
    {
        return $this->response['ResultCode'] ?? null;
    }

    /**
     * Get the Result Description from the STK Push callback
     * 
     * @return string|null The Result Description or null if not available
     */
    public function getResultDesc(): ?string
    {
        return $this->response['ResultDesc'] ?? null;
    }

    /**
     * Check if the transaction was canceled by the user
     * 
     * @return bool True if the transaction was canceled by the user, false otherwise
     */
    public function isCanceledByUser(): bool
    {
        return $this->getResultCode() === 1032;
    }

    /**
     * Get all callback data
     * 
     * @return array The complete callback response data
     */
    public function getCallbackData(): array
    {
        return $this->response;
    }

    /**
     * Set a test password for development purposes
     * This will override the normal password generation in sandbox environment only
     * 
     * @param string $testPassword The test password to use
     * @return self Returns the current instance for method chaining
     * @throws \InvalidArgumentException If attempted to set test password in production environment
     */
    private ?string $testPassword = null;

    public function setTestPassword(string $testPassword): self
    {
        if ($this->config->getEnvironment() !== 'sandbox') {
            throw new \InvalidArgumentException('Test passwords can only be set in sandbox environment');
        }
        $this->testPassword = $testPassword;
        return $this;
    }
}