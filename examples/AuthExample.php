<?php
require_once __DIR__ . '/../vendor/autoload.php';
use MesaSDK\PhpMpesa\Authentication;
use MesaSDK\PhpMpesa\Config;

// Create configuration with your credentials
$config = new Config();

$config->setEnvironment('sandbox')
    ->setConsumerKey("7oJ7uWPDp3jwqBzGvxQOn5g8s5rPwJ3qfXvsxwHyAknxAAxi")
    ->setConsumerSecret("zEvvR7yTpNYG1DoH31MKOYOzh0iZ9kdXAK1andjjrqXdnJMTbiUMhnnz5Qf12oNC");

try {
    // Initialize authentication
    $auth = new Authentication($config);

    // Disable SSL verification for sandbox environment
    $auth->setVerifySSL(false);

    // Authenticate and get token
    $auth->authenticate();

    if ($auth->hasToken()) {
        echo "Authentication successful!\n";
        echo "Access Token: " . $auth->getToken() . "\n";
        echo "Token Type: " . $auth->getTokenType() . "\n";
        echo "Expires At: " . $auth->getExpiresAt() . "\n";
    }

} catch (RuntimeException $e) {
    echo "Error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Unexpected error: " . $e->getMessage() . "\n";
}