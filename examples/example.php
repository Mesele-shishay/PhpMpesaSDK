<?php
require __DIR__ . "/../vendor/autoload.php";

use MpesaSDK\Mpesa;

$response = (new Mpesa())
    ->authenticate()
    ->setPhoneNumber("254712345678")
    ->setAmount(100)
    ->setCallbackUrl("https://your-callback-url.com")
    ->setTransactionDesc("Purchase Order #1234")
    ->setAccountReference("INV12345")
    ->initiateSTKPush();

echo json_encode($response, JSON_PRETTY_PRINT);
