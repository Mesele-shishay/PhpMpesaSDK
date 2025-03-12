<?php

require_once __DIR__ . '/../vendor/autoload.php';

use MpesaSDK\Mpesa;

// Example 1: Basic initialization with array configuration
try {
    $config = [
        'consumer_key' => 'your_consumer_key',
        'consumer_secret' => 'your_consumer_secret',
        'shortcode' => '174379', // Your business shortcode
        'passkey' => 'your_passkey',
        'environment' => 'sandbox', // or 'production' for live environment
    ];

    $mpesa = new Mpesa($config);

    // Authenticate with Safaricom's M-Pesa API
    $mpesa->authenticate();

    // Set up transaction details using method chaining
    $response = $mpesa
        ->setPhoneNumber('251714792471')
        ->setAmount(5.00)
        ->setCallbackUrl('https://example.com/callback')
        ->setTransactionDesc('Test Payment')
        ->setAccountReference('INV001')
        ->initiateSTKPush();

    // Handle the response
    print_r($response);

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
