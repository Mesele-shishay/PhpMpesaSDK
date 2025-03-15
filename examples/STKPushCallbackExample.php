<?php

require_once __DIR__ . '/../vendor/autoload.php';

use MesaSDK\PhpMpesa\Config;
use MesaSDK\PhpMpesa\Mpesa;

/**
 * This is an example of how to handle M-Pesa B2C transaction callbacks
 * This file should be hosted at the URL you specified in setResultURL()
 * For example: https://your-domain.com/callbacks/b2c-callback.php
 */


/**
 * Log callback data to a file
 * 
 * @param mixed $data The callback data to log
 * @return void
 */
function logCallbackData($data): void
{
    $logDir = __DIR__ . '/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    $logFile = $logDir . '/mpesa_callbacks_' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $logData = "[{$timestamp}] Callback Data: " . json_encode($data, JSON_PRETTY_PRINT) . "\n\n";

    file_put_contents($logFile, $logData, FILE_APPEND);
}

try {
    // Initialize M-Pesa configuration
    $config = new Config();
    $config->setEnvironment('sandbox')
        ->setBaseUrl("https://apisandbox.safaricom.et")
        ->setConsumerKey("your-consumer-key")
        ->setConsumerSecret("your-consumer-secret")
        ->setShortCode("your-shortcode")
        ->setVerifySSL(false);

    // Initialize M-Pesa
    $mpesa = new Mpesa($config);

    // Log the raw callback data
    $callbackData = file_get_contents('php://input');
    if ($callbackData) {
        logCallbackData(json_decode($callbackData, true));
    }

    // Process the callback data - it will automatically read from php://input
    $mpesa->processB2CCallback();

    // Prepare the response array
    $response = [
        'ResultCode' => 0,
        'ResultDesc' => 'Callback received successfully'
    ];

    // Check if transaction was successful
    if ($mpesa->isSuccessful()) {
        // Get transaction details
        $transactionId = $mpesa->getTransactionId();
        $conversationId = $mpesa->getConversationId();
        $amount = $mpesa->getTransactionAmount();

        // Prepare transaction details for email
        $transactionDetails = [
            'transactionId' => $transactionId,
            'conversationId' => $conversationId,
            'amount' => $amount
        ];

        // Send email notification
        $emailSent = sendTransactionEmail($transactionDetails);

        // Log transaction and email status
        error_log("Successful B2C payment: Transaction ID: {$transactionId}, Amount: {$amount}");
        if (!$emailSent) {
            error_log("Warning: Failed to send email notification for transaction: {$transactionId}");
        }

        $response['message'] = 'Payment completed successfully';
        $response['transactionDetails'] = $transactionDetails;
        $response['emailSent'] = $emailSent;
    } else {
        // Handle failed transaction
        $errorMessage = $mpesa->getErrorMessage();
        error_log("Failed B2C payment: " . $errorMessage);

        $response['ResultCode'] = 1;
        $response['ResultDesc'] = $errorMessage;
    }

} catch (\InvalidArgumentException $e) {
    // Handle invalid callback data
    error_log("Invalid callback data: " . $e->getMessage());
    $response = [
        'ResultCode' => 1,
        'ResultDesc' => 'Invalid callback data: ' . $e->getMessage()
    ];
} catch (\Exception $e) {
    // Handle any other errors
    error_log("Error processing callback: " . $e->getMessage());
    $response = [
        'ResultCode' => 1,
        'ResultDesc' => 'Error processing callback: ' . $e->getMessage()
    ];
}

// Always return a 200 OK response to M-Pesa
header('Content-Type: application/json');
http_response_code(200);
echo json_encode($response);