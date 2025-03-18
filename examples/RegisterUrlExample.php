<?php

require_once __DIR__ . '/../vendor/autoload.php';

use MesaSDK\PhpMpesa\Mpesa;
use MesaSDK\PhpMpesa\Config;
use MesaSDK\PhpMpesa\Exceptions\MpesaException;


// Create configuration with your credentials
$config = new Config();
$config->setEnvironment('sandbox')
    ->setVerifySSL(false)
    ->setAutoAuthenticate(false); // Disable automatic authentication


// Example usage
try {
    // Initialize the class with your API credentials
    $mpesa = new Mpesa($config);
    $mpesa->setApiKey('7oJ7uWPDp3jwqBzGvxQOn5g8s5rPwJ3qfXvsxwHyAknxAAxi');

    // Manually authenticate before making any requests

    // Register URLs using the public method
    $response = $mpesa->setShortcode('6989')
        ->registerUrls(
            'https://your-domain.com/confirmation',  // confirmationUrl
            'https://your-domain.com/validation'     // validationUrl
        );
    // Handle the response
    if (is_array($response)) {
        echo "Success: " . ($response['ResponseDescription'] ?? 'URL registration successful') . "\n";
    } else {
        echo "Response: " . $response . "\n";
    }
} catch (MpesaException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}