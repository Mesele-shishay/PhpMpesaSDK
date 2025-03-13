<?php

require_once __DIR__ . '/vendor/autoload.php';

use MesaSDK\PhpMpesa\Config;
use MesaSDK\PhpMpesa\Mpesa;

// Create configuration with your credentials
$config = new Config();
$config->setEnvironment('sandbox')
    ->setBaseUrl("https://apisandbox.safaricom.et")
    ->setConsumerKey("7oJ7uWPDp3jwqBzGvxQOn5g8s5rPwJ3qfXvsxwHyAknxAAxi")
    ->setConsumerSecret("zEvvR7yTpNYG1DoH31MKOYOzh0iZ9kdXAK1andjjrqXdnJMTbiUMhnnz5Qf12oNC")
    ->setShortCode("1020");

try {
    // Initialize Mpesa
    $mpesa = new Mpesa($config);
    $mpesa->setVerifySSL(false)
        ->authenticate()
        ->setPhoneNumber('251700404709')
        ->setAmount(20)
        ->setTestPassword('M2VkZGU2YWY1Y2RhMzIyOWRjMmFkMTRiMjdjOWIwOWUxZDFlZDZiNGQ0OGYyMDRiNjg0ZDZhNWM2NTQyNTk2ZA==')
        ->setCallbackUrl('https://www.myservice:8080/result');

    // Initiate C2B transaction
    $mpesa->initiateSTKPush();

    // Handle the response
    if ($mpesa->isSuccessful()) {
        echo "Transaction initiated successfully!\n";
        echo "Merchant Request ID: " . $mpesa->getMerchantRequestID() . "\n";
        echo "Checkout Request ID: " . $mpesa->getCheckoutRequestID() . "\n";
    } else {
        echo "Transaction failed!\n";
    }

} catch (RuntimeException $e) {
    echo "Error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Unexpected error: " . $e->getMessage() . "\n";
}