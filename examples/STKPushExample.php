<?php
require_once __DIR__ . '/../vendor/autoload.php';
use MesaSDK\PhpMpesa\Config;
use MesaSDK\PhpMpesa\Mpesa;

// Create configuration with your credentials
$config = new Config();
$config->setEnvironment('sandbox')
    ->setBaseUrl("https://apisandbox.safaricom.et")
    ->setConsumerKey("7oJ7uWPDp3jwqBzGvxQOn5g8s5rPwJ3qfXvsxwHyAknxAAxi")
    ->setConsumerSecret("zEvvR7yTpNYG1DoH31MKOYOzh0iZ9kdXAK1andjjrqXdnJMTbiUMhnnz5Qf12oNC")
    ->setShortCode("1020")
    ->setVerifySSL(false);

try {
    // Initialize Mpesa
    $mpesa = new Mpesa($config);

    // Set up the STK Push request
    $mpesa->authenticate()
        ->setPhoneNumber('251700404709')
        ->setAmount(20)
        ->setAccountReference('gfgfhg')  // Added account reference
        ->setTransactionDesc('Monthly Unlimited Package via Chatbot')  // Added transaction description
        ->setCallbackUrl('https://testt.tugza.tech/examples/STKPushCallbackExample.php');

    // For sandbox testing only
    if ($config->getEnvironment() === 'sandbox') {
        $mpesa->setTestPassword('M2VkZGU2YWY1Y2RhMzIyOWRjMmFkMTRiMjdjOWIwOWUxZDFlZDZiNGQ0OGYyMDRiNjg0ZDZhNWM2NTQyNTk2ZA==');
    }

    // Initiate C2B transaction
    $mpesa->initiateSTKPush();


    // Handle the response
    if ($mpesa->isSuccessful()) {
        echo "Transaction initiated successfully!\n";
        echo "Merchant Request ID: " . $mpesa->getMerchantRequestID() . "\n";
        echo "Checkout Request ID: " . $mpesa->getCheckoutRequestID() . "\n";

        // Get full callback data if available
        $callbackData = $mpesa->getCallbackData();
        if (!empty($callbackData)) {
            echo "Response Data:\n";
            print_r($callbackData);

            // Check if user canceled
            if ($mpesa->isCanceledByUser()) {
                echo "Transaction was canceled by the user\n";
            }

            // Get result details
            echo "Result Code: " . $mpesa->getResultCode() . "\n";
            echo "Result Description: " . $mpesa->getResultDesc() . "\n";
        }
    } else {
        echo "Transaction failed!\n";
        if ($mpesa->getResultDesc()) {
            echo "Reason: " . $mpesa->getResultDesc() . "\n";
        }
    }

} catch (RuntimeException $e) {
    echo "Error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Unexpected error: " . $e->getMessage() . "\n";
}