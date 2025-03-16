<?php

require_once __DIR__ . '/../vendor/autoload.php';

use MesaSDK\PhpMpesa\Mpesa;
use MesaSDK\PhpMpesa\Config;
use MesaSDK\PhpMpesa\Exceptions\MpesaException;


// Create configuration with your credentials
$config = new Config();
$config->setEnvironment('sandbox')
    ->setVerifySSL(false);



// Example usage
try {
    // Initialize the class with your API credentials
    $mpesa = new Mpesa($config);
    $mpesa->setApiKey('7oJ7uWPDp3jwqBzGvxQOn5g8s5rPwJ3qfXvsxwHyAknxAAxi');
    $response = $mpesa->registerUrl(
        '81100',  // shortCode
        'Completed',  // responseType
        'https://your-domain.com/confirmation',  // confirmationUrl
        'https://your-domain.com/validation',  // validationUrl
        'RegisterURL'  // commandId (optional)
    );
    echo $response->getResponseMessage();
} catch (MpesaException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}