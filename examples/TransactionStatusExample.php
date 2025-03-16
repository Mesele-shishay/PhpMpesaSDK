<?php

require_once __DIR__ . '/../vendor/autoload.php';

use MesaSDK\PhpMpesa\Config;
use MesaSDK\PhpMpesa\Mpesa;
use MesaSDK\PhpMpesa\Exceptions\MpesaException;
use MesaSDK\PhpMpesa\Models\TransactionStatusResponse;

// Load settings from environment or configuration file
$settings = [
    'environment' => 'sandbox', // or 'production'
    'base_url' => 'https://apisandbox.safaricom.et',
    'consumer_key' => 'QeZ1WgHxMJCngVLGbsHwMQSmZO7HHnQjGGbeSH3VaKB90fta',
    'consumer_secret' => 'bM7gNvNTXH7T3IPzUAIYpa4xzlENgGPC4raksDXWt2VvjcquzgD80P3G6cM01BEv',
    'shortcode' => '174379',
    'result_url' => 'https://your-domain.com/api/mpesa/transaction-status/result',
    'timeout_url' => 'https://your-domain.com/api/mpesa/transaction-status/timeout'
];

try {
    // Initialize configuration
    $config = new Config();
    $config->setEnvironment($settings['environment'])
        ->setBaseUrl($settings['base_url'])
        ->setConsumerKey($settings['consumer_key'])
        ->setConsumerSecret($settings['consumer_secret'])
        ->setShortCode($settings['shortcode'])
        ->setVerifySSL(false); // Note: Always use true in production

    // Initialize M-Pesa client
    $mpesa = new Mpesa($config);

    // Authenticate
    $mpesa->authenticate();

    // Query transaction status using fluent API
    /** @var TransactionStatusResponse $response */
    $response = $mpesa
        ->setStatusInitiator('apitest')
        ->setStatusSecurityCredential('lMhf0UqE4ydeEDwpUskmPgkNDZnA6NLi7z3T1TQuWCkH3/ScW8pRRnobq/AcwFvbC961+zDMgOEYGm8Oivb7L/7Y9ED3lhR7pJvnH8B1wYis5ifdeeWI6XE2NSq8X1Tc7QB9Dg8SlPEud3tgloB2DlT+JIv3ebIl/J/8ihGVrq499bt1pz/EA2nzkCtGeHRNbEDxkqkEnbioV0OM//0bv4K++XyV6jUFlIIgkDkmcK6aOU8mPBHs2um9aP+Y+nTJaa6uHDudRFg0+3G6gt1zRCPs8AYbts2IebseBGfZKv5K6Lqk9/W8657gEkrDZE8Mi78MVianqHdY/8d6D9KKhw==')
        ->setTransactionId('0')
        ->setResultUrl($settings['result_url'])
        ->setQueueTimeOutUrl($settings['timeout_url'])
        ->setRemarks('Transaction Status Query')
        ->setStatusOccasion('Query trans status')
        ->queryTransactionStatus();

    // Check if request was successful
    if ($response->isSuccessful()) {
        echo "Transaction status query initiated successfully\n";

        // Access specific transaction details using the model methods
        echo "Transaction Status: " . $response->getTransactionStatus() . "\n";
        echo "Amount: " . $response->getAmount() . "\n";
        echo "Transaction Date: " . $response->getTransactionDate() . "\n";
        echo "Phone Number: " . $response->getPhoneNumber() . "\n";
        echo "Receipt Number: " . $response->getReceiptNumber() . "\n";
        echo "Debit Party: " . $response->getDebitPartyName() . "\n";
        echo "Credit Party: " . $response->getCreditPartyName() . "\n";

        // Check if transaction is completed
        if ($response->isCompleted()) {
            echo "Transaction is completed\n";
        } else {
            echo "Transaction is not completed\n";
        }

        // Get any additional parameter
        $customParam = $response->getResultParameter('CustomKey', 'default value');
        echo "Custom Parameter: " . $customParam . "\n";

        // Get all data as array or JSON
        echo "All Data (JSON): " . $response->toJson() . "\n";
    } else {
        echo "Transaction status query failed\n";
        echo "Error Code: " . $response->getResultCode() . "\n";
        echo "Error Description: " . $response->getResultDesc() . "\n";
    }

} catch (MpesaException $e) {
    echo "M-Pesa Error: " . $e->getMessage() . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Example callback handler
if (php_sapi_name() !== 'cli' && isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/api/mpesa/transaction-status/result') !== false) {
        // Get the raw POST data
        $rawData = file_get_contents('php://input');
        $callbackData = json_decode($rawData, true);

        if ($callbackData) {
            try {
                // Process the callback data using the new model
                /** @var TransactionStatusResponse $processedData */
                $processedData = $mpesa->processTransactionStatusCallback($callbackData);

                // Log the processed data
                error_log('Transaction Status Callback: ' . $processedData->toJson());

                // Use the model methods to access data
                $transactionStatus = $processedData->getTransactionStatus();
                $amount = $processedData->getAmount();
                $transactionDate = $processedData->getTransactionDate();
                $phoneNumber = $processedData->getPhoneNumber();

                // Do something with the transaction information
                if ($processedData->isCompleted()) {
                    // Transaction was successful
                    // Update your database, send notifications, etc.
                    $receiptNumber = $processedData->getReceiptNumber();
                    // Store receipt number in database
                } else {
                    // Transaction failed or is in another state
                    // Handle accordingly
                }

                // Always respond with a success to M-Pesa
                header('Content-Type: application/json');
                echo json_encode([
                    'ResultCode' => 0,
                    'ResultDesc' => 'Success'
                ]);
            } catch (Exception $e) {
                error_log('Error processing transaction status callback: ' . $e->getMessage());
                // Still return success to M-Pesa
                header('Content-Type: application/json');
                echo json_encode([
                    'ResultCode' => 0,
                    'ResultDesc' => 'Success'
                ]);
            }
        }
    }
}