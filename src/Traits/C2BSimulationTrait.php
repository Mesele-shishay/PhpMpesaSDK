<?php

namespace MesaSDK\PhpMpesa\Traits;

use MesaSDK\PhpMpesa\Exceptions\MpesaException;
use MesaSDK\PhpMpesa\Models\C2BSimulationResponse;

trait C2BSimulationTrait
{
    /** @var string The command ID for C2B simulation */
    protected string $c2bCommandId = 'CustomerPayBillOnline';

    /** @var string|null The bill reference number */
    protected ?string $c2bBillRefNumber = null;

    /** @var string|null The MSISDN (phone number) */
    protected ?string $c2bMsisdn = null;

    /** @var float|null The transaction amount */
    protected ?float $c2bAmount = null;

    /**
     * Set the command ID for C2B simulation
     * 
     * @param string $commandId The command ID (must be CustomerPayBillOnline)
     * @return self Returns the current instance for method chaining
     * @throws MpesaException If command ID is invalid
     */
    public function setC2BCommandId(string $commandId): self
    {
        if ($commandId !== 'CustomerPayBillOnline') {
            throw new MpesaException('Invalid CommandID. Must be CustomerPayBillOnline');
        }
        $this->c2bCommandId = $commandId;
        return $this;
    }

    /**
     * Set the bill reference number for C2B simulation
     * 
     * @param string $billRefNumber The bill reference number
     * @return self Returns the current instance for method chaining
     */
    public function setC2BBillRefNumber(string $billRefNumber): self
    {
        $this->c2bBillRefNumber = $billRefNumber;
        return $this;
    }

    /**
     * Set the MSISDN (phone number) for C2B simulation
     * 
     * @param string $msisdn The customer's phone number
     * @return self Returns the current instance for method chaining
     * @throws MpesaException If phone number format is invalid
     */
    public function setC2BMsisdn(string $msisdn): self
    {
        if (!preg_match('/^251[17]\d{8}$/', $msisdn)) {
            throw new MpesaException('Phone number must be in the format 251XXXXXXXXX');
        }
        $this->c2bMsisdn = $msisdn;
        return $this;
    }

    /**
     * Set the amount for C2B simulation
     * 
     * @param float $amount The transaction amount
     * @return self Returns the current instance for method chaining
     * @throws MpesaException If amount is not positive
     */
    public function setC2BAmount(float $amount): self
    {
        if ($amount <= 0) {
            throw new MpesaException('Amount must be greater than 0');
        }
        $this->c2bAmount = $amount;
        return $this;
    }

    /**
     * Simulate a C2B transaction
     * 
     * @param string $commandId The command ID (CustomerPayBillOnline)
     * @param float $amount The transaction amount
     * @param string $msisdn The customer's phone number
     * @param string $billRefNumber The bill reference number
     * @param string $shortCode The organization's shortcode
     * @return C2BSimulationResponse
     * @throws MpesaException
     */
    public function simulateC2B(
        string $commandId,
        float $amount,
        string $msisdn,
        string $billRefNumber,
        string $shortCode
    ): C2BSimulationResponse {
        // Validate phone number format
        if (!preg_match('/^251[17]\d{8}$/', $msisdn)) {
            throw new MpesaException('Phone number must be in the format 251XXXXXXXXX');
        }

        // Validate amount
        if ($amount <= 0) {
            throw new MpesaException('Amount must be greater than 0');
        }

        // Validate command ID
        if ($commandId !== 'CustomerPayBillOnline') {
            throw new MpesaException('Invalid CommandID. Must be CustomerPayBillOnline');
        }

        $payload = [
            'CommandID' => $commandId,
            'Amount' => (string) $amount,
            'Msisdn' => $msisdn,
            'BillRefNumber' => $billRefNumber,
            'ShortCode' => $shortCode
        ];

        $response = $this->executeRequest(
            'POST',
            '/mpesa/b2c/simulatetransaction/v1/request',
            $payload
        );

        return C2BSimulationResponse::fromArray($response);
    }

    /**
     * Execute C2B simulation with previously set parameters
     * 
     * @return C2BSimulationResponse
     * @throws MpesaException If required parameters are not set
     */
    protected function executeC2BSimulation(): C2BSimulationResponse
    {
        if ($this->c2bAmount === null) {
            throw new MpesaException('Amount must be set before executing C2B simulation');
        }

        if ($this->c2bMsisdn === null) {
            throw new MpesaException('MSISDN (phone number) must be set before executing C2B simulation');
        }

        if ($this->c2bBillRefNumber === null) {
            throw new MpesaException('Bill reference number must be set before executing C2B simulation');
        }

        return $this->simulateC2B(
            $this->c2bCommandId,
            $this->c2bAmount,
            $this->c2bMsisdn,
            $this->c2bBillRefNumber,
            $this->config->getShortcode()
        );
    }
}