<?php

require_once __DIR__ . '/vendor/autoload.php';

use MesaSDK\PhpMpesa\Authentication;
use MesaSDK\PhpMpesa\Config;
use MesaSDK\PhpMpesa\Mpesa;

// Create configuration with your credentials
$config = new Config();
$config->setEnvironment('sandbox')
       ->setBaseUrl("https://apisandbox.safaricom.et")
       ->setConsumerKey("7oJ7uWPDp3jwqBzGvxQOn5g8s5rPwJ3qfXvsxwHyAknxAAxi")
       ->setConsumerSecret("zEvvR7yTpNYG1DoH31MKOYOzh0iZ9kdXAK1andjjrqXdnJMTbiUMhnnz5Qf12oNC")
       ->setShortCode("174379");  // Your organization's shortcode

try {
    // Initialize Mpesa
    $mpesa = new Mpesa($config);
    // Disable SSL verification for sandbox environment
    $mpesa->setVerifySSL(false);
    $mpesa->authenticate();
    $mpesa->setPhoneNumber('251714792471');
    $mpesa->setAmount(100);
    $mpesa->setCallbackUrl('https://example.com/callback');

    // Initiate C2B transaction
    $response = $mpesa->initiateSTKPush($params);

    // Handle the response
    if ($response->isSuccessful()) {
        echo "Transaction initiated successfully!\n";
        echo "Conversation ID: " . $response->getConversationID() . "\n";
        echo "Origin Conversation ID: " . $response->getOriginatorConversationID() . "\n";
        echo "Response Description: " . $response->getResponseDescription() . "\n";
    } else {
        echo "Transaction failed!\n";
        echo "Error: " . $response->getErrorMessage() . "\n";
    }

} catch (RuntimeException $e) {
    echo "Error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Unexpected error: " . $e->getMessage() . "\n";
} 