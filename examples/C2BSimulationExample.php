<?php

require_once __DIR__ . '/../vendor/autoload.php';

use MesaSDK\PhpMpesa\Config;
use MesaSDK\PhpMpesa\Mpesa;
use MesaSDK\PhpMpesa\Exceptions\MpesaException;
use MesaSDK\PhpMpesa\Models\C2BSimulationResponse;

// Load configuration
$config = new Config();
$config->setBaseUrl("https://apisandbox.safaricom.et")
    ->setConsumerKey("QeZ1WgHxMJCngVLGbsHwMQSmZO7HHnQjGGbeSH3VaKB90fta")
    ->setConsumerSecret("bM7gNvNTXH7T3IPzUAIYpa4xzlENgGPC4raksDXWt2VvjcquzgD80P3G6cM01BEv")
    ->setEnvironment('sandbox')
    ->setShortcode('443443')
    ->setVerifySSL(false);  // Note: Always use true in production

// Initialize M-Pesa client
$mpesa = new Mpesa($config);

try {
    // 1. First, authenticate with M-Pesa API
    $mpesa->authenticate();

    // 2. Simulate a C2B payment using the fluent interface (recommended)
    /** @var C2BSimulationResponse $response */
    $response = $mpesa
        ->setC2BAmount(110.00)                // Set amount
        ->setC2BMsisdn('251745628580')       // Set customer phone number
        ->setC2BBillRefNumber('091091')      // Set bill reference number
        ->executeC2BSimulation();            // Execute the simulation

    // 3. Handle the response using the model methods
    if ($response->isSuccessful()) {
        echo "✅ C2B payment simulation initiated successfully!\n";
        echo "Response Code: " . $response->getResponseCode() . "\n";
        echo "Description: " . $response->getResponseDescription() . "\n";
        echo "Conversation ID: " . $response->getConversationId() . "\n";
        echo "Originator Conversation ID: " . $response->getOriginatorConversationId() . "\n";
        echo "Customer Message: " . $response->getCustomerMessage() . "\n";
        echo "Merchant Request ID: " . $response->getMerchantRequestId() . "\n";
        echo "Checkout Request ID: " . $response->getCheckoutRequestId() . "\n";

        // You can also get the response as an array
        $responseArray = $response->toArray();
        echo "\nFull Response Array:\n";
        print_r($responseArray);
    } else {
        echo "❌ C2B payment simulation failed!\n";
        echo "Error Code: " . $response->getResponseCode() . "\n";
        echo "Error Description: " . $response->getResponseDescription() . "\n";
    }

} catch (MpesaException $e) {
    echo "❌ M-Pesa Error: " . $e->getMessage() . "\n";
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}