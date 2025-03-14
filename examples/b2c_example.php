<?php

require_once __DIR__ . '/../vendor/autoload.php';

use MesaSDK\PhpMpesa\Config;
use MesaSDK\PhpMpesa\Mpesa;
use MesaSDK\PhpMpesa\MpesaException;

// Initialize configuration
$config = new Config();

$config->setEnvironment('sandbox')
    ->setVerifySSL(false)
    ->setConsumerKey("7oJ7uWPDp3jwqBzGvxQOn5g8s5rPwJ3qfXvsxwHyAknxAAxi")
    ->setConsumerSecret("zEvvR7yTpNYG1DoH31MKOYOzh0iZ9kdXAK1andjjrqXdnJMTbiUMhnnz5Qf12oNC");


// Create Mpesa instance
$mpesa = new Mpesa($config);


try {


    // Example 2: Using the fluent interface with custom URLs
    echo "\nExample 2: Using fluent interface with custom URLs\n";
    $result2 = $mpesa
        ->authenticate()
        ->setInitiatorName('apitest')
        ->setSecurityCredential('your_security_credential')
        ->setCommandID('SalaryPayment')
        ->setAmount(250.75)
        ->setPartyA('1234567')
        ->setPartyB('251700404709')
        ->setRemarks('Bonus payment')
        ->setOccasion('Performance Bonus')
        ->setQueueTimeOutURL('https://your-domain.com/timeout')
        ->setResultURL('https://your-domain.com/result')
        ->send();

    print_r($result2->getResponseMessage());

} catch (MpesaException $e) {
    echo "Error occurred: " . $e->getMessage() . "\n";
    echo "Error code: " . $e->getCode() . "\n";
} catch (Exception $e) {
    echo "Unexpected error: " . $e->getMessage() . "\n";
}