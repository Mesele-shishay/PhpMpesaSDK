<?php
require_once __DIR__ . '/../vendor/autoload.php';

use MesaSDK\PhpMpesa\Config;
use MesaSDK\PhpMpesa\Mpesa;
use MesaSDK\PhpMpesa\Logging\MpesaLogger;
use Psr\Log\LogLevel;

// Create configuration with default logging settings
$defaultConfig = new Config();
$defaultConfig->setEnvironment('sandbox')
    ->setBaseUrl("https://apisandbox.safaricom.et")
    ->setConsumerKey("your_consumer_key")
    ->setConsumerSecret("your_consumer_secret")
    ->setShortcode("your_shortcode")
    ->setVerifySSL(false)
    // This will use all default logging settings
    ->setLoggingConfig([]);

// Create configuration with custom logging settings
$customConfig = new Config();
$customConfig->setEnvironment('sandbox')
    ->setBaseUrl("https://apisandbox.safaricom.et")
    ->setConsumerKey("your_consumer_key")
    ->setConsumerSecret("your_consumer_secret")
    ->setShortcode("your_shortcode")
    ->setVerifySSL(false)
    // Override only specific logging settings
    ->setLoggingConfig([
        'log_dir' => 'custom_logs',
        'log_to_console' => true,
        'min_log_level' => LogLevel::DEBUG,
        'log_format' => '[%datetime%] [%level%] %message% %context%'
        // max_file_size and max_files will use default values (null)
    ]);

try {
    // Initialize Mpesa with default logging
    $mpesaDefault = new Mpesa($defaultConfig);

    // Initialize Mpesa with custom logging
    $mpesaCustom = new Mpesa($customConfig);

    // Example STK Push with custom logging
    $mpesaCustom->authenticate()
        ->setPhoneNumber('251700404709')
        ->setAmount(20)
        ->setAccountReference('test_ref')
        ->setTransactionDesc('Test Payment')
        ->setCallbackUrl('https://example.com/callback');

    // Initiate STK Push
    $result = $mpesaCustom->initiateSTKPush();

} catch (Exception $e) {
    $logger = $mpesaCustom->getLogger();
    $logger->logException($e, [
        'operation' => 'STK Push',
        'phone' => '251700404709',
        'amount' => 20
    ]);

    echo "Error: " . $e->getMessage() . "\n";
}

// Example of different log levels with context
$logger = $mpesaCustom->getLogger();

// System-level events
$logger->emergency('Critical system error', [
    'error' => 'Database connection failed',
    'server' => 'primary'
]);

$logger->alert('Security alert', [
    'ip' => '192.168.1.1',
    'attempt' => 'unauthorized access'
]);

// Application-level events
$logger->critical('Payment processing failed', [
    'transaction_id' => '123',
    'reason' => 'timeout'
]);

$logger->error('Invalid API response', [
    'response' => 'error 404',
    'endpoint' => '/api/payments'
]);

// Warning and informational events
$logger->warning('Deprecated method used', [
    'method' => 'oldMethod',
    'alternative' => 'newMethod'
]);

$logger->notice('User action required', [
    'action' => 'verify_phone',
    'user_id' => '12345'
]);

$logger->info('Transaction successful', [
    'amount' => 1000,
    'reference' => 'TRX123'
]);

$logger->debug('Debug information', [
    'request_id' => 'abc123',
    'processing_time' => '2.5s'
]);