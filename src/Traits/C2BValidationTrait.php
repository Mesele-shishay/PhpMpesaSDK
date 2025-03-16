<?php

namespace MesaSDK\PhpMpesa\Traits;

use MesaSDK\PhpMpesa\Exceptions\MpesaException;

trait C2BValidationTrait
{
    /**
     * Handle C2B validation request
     * 
     * @param array $request The validation request data
     * @return array The validation response
     * @throws MpesaException
     */
    public function handleValidation(array $request): array
    {
        // Validate required fields
        $requiredFields = [
            'TransID',
            'TransAmount',
            'BusinessShortCode',
            'BillRefNumber',
            'MSISDN'
        ];

        foreach ($requiredFields as $field) {
            if (!isset($request[$field])) {
                return [
                    'ResultCode' => 'C2B00016',
                    'ResultDesc' => "Missing required field: {$field}",
                    'ThirdPartyTransID' => ''
                ];
            }
        }

        // Validate MSISDN format (Ethiopian phone number)
        if (!preg_match('/^251[17]\d{8}$/', $request['MSISDN'])) {
            return [
                'ResultCode' => 'C2B00011',
                'ResultDesc' => 'Invalid MSISDN',
                'ThirdPartyTransID' => ''
            ];
        }

        // Validate amount (must be numeric and positive)
        if (!is_numeric($request['TransAmount']) || floatval($request['TransAmount']) <= 0) {
            return [
                'ResultCode' => 'C2B00013',
                'ResultDesc' => 'Invalid Amount',
                'ThirdPartyTransID' => ''
            ];
        }

        // Validate BusinessShortCode
        if (!preg_match('/^\d{5,6}$/', $request['BusinessShortCode'])) {
            return [
                'ResultCode' => 'C2B00015',
                'ResultDesc' => 'Invalid Shortcode',
                'ThirdPartyTransID' => ''
            ];
        }

        // Generate a unique ThirdPartyTransID
        $thirdPartyTransID = 'TXN' . time() . rand(1000, 9999);

        // Return success response
        return [
            'ResultCode' => '0',
            'ResultDesc' => 'Accepted',
            'ThirdPartyTransID' => $thirdPartyTransID
        ];
    }

    /**
     * Handle C2B confirmation request
     * 
     * @param array $request The confirmation request data
     * @return array The confirmation response
     */
    public function handleConfirmation(array $request): array
    {
        // Log the confirmation request for tracking
        if (method_exists($this, 'log')) {
            $this->log('info', 'C2B Confirmation received', $request);
        }

        // Return success response
        return [
            'ResultCode' => '0',
            'ResultDesc' => 'Success'
        ];
    }

    /**
     * Validate transaction amount
     * 
     * @param string|float $amount
     * @return bool
     */
    protected function validateAmount($amount): bool
    {
        return is_numeric($amount) && floatval($amount) > 0;
    }

    /**
     * Validate Ethiopian phone number
     * 
     * @param string $msisdn
     * @return bool
     */
    protected function validateMSISDN(string $msisdn): bool
    {
        return preg_match('/^251[17]\d{8}$/', $msisdn) === 1;
    }
}