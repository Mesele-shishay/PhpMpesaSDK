<?php

require_once __DIR__ . '/../vendor/autoload.php';

use MesaSDK\PhpMpesa\Config;
use MesaSDK\PhpMpesa\Mpesa;
use MesaSDK\PhpMpesa\Exceptions\MpesaException;

// Initialize configuration
$config = new Config(
);

$config->setBaseUrl("https://apisandbox.safaricom.et")
    ->setConsumerKey("7oJ7uWPDp3jwqBzGvxQOn5g8s5rPwJ3qfXvsxwHyAknxAAxi")
    ->setConsumerSecret("zEvvR7yTpNYG1DoH31MKOYOzh0iZ9kdXAK1andjjrqXdnJMTbiUMhnnz5Qf12oNC")
    ->setEnvironment('sandbox')
    ->setVerifySSL(false);


// Create Mpesa instance
$mpesa = new Mpesa($config);

try {
    // Authenticate first
    $mpesa->authenticate();

    // Use the b2c method with positional arguments
    $result2 = $mpesa->b2c(
        'apitest',                // initiatorName
        'your_security_credential', // securityCredential
        'SalaryPayment',          // commandId
        250.75,                   // amount
        '1234567',               // partyA
        '251700404709',          // partyB
        'Bonus payment',          // remarks
        'Performance Bonus',      // occasion
        'https://your-domain.com/timeout', // queueTimeOutURL
        'https://your-domain.com/result'   // resultURL
    );

    print_r($result2->getResponseMessage());

} catch (MpesaException $e) {
    echo "Error occurred: " . $e->getMessage() . "\n";
    echo "Error code: " . $e->getCode() . "\n";
} catch (Exception $e) {
    echo "Unexpected error: " . $e->getMessage() . "\n";
}