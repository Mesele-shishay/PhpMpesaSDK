<?php
/**
 * M-Pesa STK Push Example
 * 
 * This example demonstrates how to initiate an STK Push request
 * to prompt a customer to make a payment through the M-Pesa menu on their phone.
 * 
 * Prerequisites:
 * - Valid M-Pesa API credentials (Consumer Key and Secret)
 * - A registered M-Pesa shortcode
 * - SSL enabled callback URL
 */

require_once __DIR__ . '/../vendor/autoload.php';
use MesaSDK\PhpMpesa\Config;
use MesaSDK\PhpMpesa\Mpesa;
use MesaSDK\PhpMpesa\Exceptions\MpesaException;

// Configuration settings
// In production, these should be loaded from environment variables or a config file
$settings = [
    'environment' => 'sandbox',
    'base_url' => 'https://apisandbox.safaricom.et',
    'consumer_key' => '7oJ7uWPDp3jwqBzGvxQOn5g8s5rPwJ3qfXvsxwHyAknxAAxi',
    'consumer_secret' => 'zEvvR7yTpNYG1DoH31MKOYOzh0iZ9kdXAK1andjjrqXdnJMTbiUMhnnz5Qf12oNC',
    'shortcode' => '1020',
    'callback_url' => 'https://testt.tugza.tech/examples/STKPushCallbackExample.php'
];

try {
    // 1. Initialize configuration
    $config = new Config();
    $config->setEnvironment($settings['environment'])
        ->setBaseUrl($settings['base_url'])
        ->setConsumerKey($settings['consumer_key'])
        ->setConsumerSecret($settings['consumer_secret'])
        ->setShortCode($settings['shortcode'])
        ->setVerifySSL(false); // Note: Always use true in production

    // 2. Initialize M-Pesa client
    $mpesa = new Mpesa($config);

    // 3. Set up the transaction details
    $mpesa->authenticate()
        ->setPhoneNumber('251700404709')  // Customer's phone number (format: 2517XXXXXXXX)
        ->setAmount(20.00)                // Amount to be charged
        ->setAccountReference('INV123')   // Your unique transaction reference
        ->setTransactionDesc('Payment for Monthly Package')  // Description shown to customer
        ->setCallbackUrl($settings['callback_url']);

    // 4. For sandbox testing only - set test credentials
    if ($config->getEnvironment() === 'sandbox') {
        $mpesa->setTestPassword('M2VkZGU2YWY1Y2RhMzIyOWRjMmFkMTRiMjdjOWIwOWUxZDFlZDZiNGQ0OGYyMDRiNjg0ZDZhNWM2NTQyNTk2ZA==');
    }

    // 5. Initiate the STK Push
    $response = $mpesa->initiateSTKPush();

    // 6. Handle the response
    if ($mpesa->isSuccessful()) {
        echo "✅ Transaction initiated successfully!\n\n";
        echo "Transaction Details:\n";
        echo "-------------------\n";
        echo "🔖 Merchant Request ID: " . $mpesa->getMerchantRequestID() . "\n";
        echo "🔖 Checkout Request ID: " . $mpesa->getCheckoutRequestID() . "\n\n";

        // Store these IDs for later use in callback handling

        // 7. Check for callback data (if synchronous)
        $callbackData = $mpesa->getCallbackData();
        if (!empty($callbackData)) {
            echo "Callback Response:\n";
            echo "----------------\n";
            print_r($callbackData);

            if ($mpesa->isCanceledByUser()) {
                echo "❌ Transaction was canceled by the user\n";
            }

            echo "Result Code: " . $mpesa->getResultCode() . "\n";
            echo "Result Description: " . $mpesa->getResultDesc() . "\n";
        } else {
            echo "ℹ️ Waiting for customer to complete the payment...\n";
            echo "Check STKPushCallbackExample.php for callback handling\n";
        }
    } else {
        echo "❌ Transaction initiation failed!\n";
        echo "Error: " . $mpesa->getResultDesc() . "\n";
    }

} catch (MpesaException $e) {
    echo "❌ M-Pesa API Error: " . $e->getMessage() . "\n";
    // Log the error for debugging
    error_log("M-Pesa Error: " . $e->getMessage());
} catch (RuntimeException $e) {
    echo "❌ Runtime Error: " . $e->getMessage() . "\n";
    error_log("Runtime Error: " . $e->getMessage());
} catch (Exception $e) {
    echo "❌ Unexpected Error: " . $e->getMessage() . "\n";
    error_log("Unexpected Error: " . $e->getMessage());
}

