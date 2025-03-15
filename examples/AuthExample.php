<?php
require_once __DIR__ . '/../vendor/autoload.php';
use MesaSDK\PhpMpesa\Authentication;
use MesaSDK\PhpMpesa\Config;
use MesaSDK\PhpMpesa\Exceptions\MpesaException;

// Create configuration with your credentials
$config = new Config();

$config->setEnvironment('sandbox')
    ->setConsumerKey("QeZ1WgHxMJCngVLGbsHwMQSmZO7HHnQjGGbeSH3VaKB90fta")->setVerifySSL(false)
    ->setConsumerSecret("bM7gNvNTXH7T3IPzUAIYpa4xzlENgGPC4raksDXWt2VvjcquzgD80P3G6cM01BEv");

try {
    // Initialize authentication
    $auth = new Authentication($config);

    // Disable SSL verification for sandbox environment

    // Authenticate and get token
    $auth->authenticate();

    if ($auth->hasToken()) {
        echo "Authentication successful!\n";
        echo "Access Token: " . $auth->getToken() . "\n";
        echo "Token Type: " . $auth->getTokenType() . "\n";
        echo "Expires At: " . $auth->getExpiresAt() . "\n";
    }

} catch (MpesaException $e) {
    print_r($e->getMessage());
}