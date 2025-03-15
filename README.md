# M-Pesa PHP SDK

A robust and developer-friendly PHP SDK for integrating M-Pesa payment services into your applications.

## Features

- 🔒 Secure Authentication
- 💳 STK Push
- 💸 B2C Payments
- 📱 C2B Payments
- 🔄 Transaction Status Queries
- 📨 URL Registration
- 📝 Comprehensive Logging
- ⚡ Asynchronous Callbacks

## Requirements

- PHP 7.4 or higher
- Composer
- Valid M-Pesa API credentials
- SSL enabled callback URL

## Installation

Install the package via Composer:

```bash
composer require mesa/php-mpesa
```

## Quick Start

```php
use MesaSDK\PhpMpesa\Config;
use MesaSDK\PhpMpesa\Mpesa;

// Initialize configuration
$config = new Config();
$config->setEnvironment('sandbox')
      ->setConsumerKey('your_consumer_key')
      ->setConsumerSecret('your_consumer_secret')
      ->setShortCode('your_shortcode');

// Initialize M-Pesa
$mpesa = new Mpesa($config);

// Example: Initiate STK Push
try {
    $response = $mpesa->authenticate()
        ->setPhoneNumber('2517XXXXXXXX')
        ->setAmount(100)
        ->setAccountReference('INV123')
        ->setTransactionDesc('Payment for Order #123')
        ->setCallbackUrl('https://your-domain.com/callback')
        ->initiateSTKPush();

    if ($mpesa->isSuccessful()) {
        $checkoutRequestId = $mpesa->getCheckoutRequestID();
        // Store checkoutRequestId for later use
    }
} catch (Exception $e) {
    // Handle error
}
```

## Configuration

### Environment Variables

We recommend using environment variables for sensitive configuration:

```php
$config = new Config();
$config->setEnvironment($_ENV['MPESA_ENVIRONMENT'])
      ->setConsumerKey($_ENV['MPESA_CONSUMER_KEY'])
      ->setConsumerSecret($_ENV['MPESA_CONSUMER_SECRET'])
      ->setShortCode($_ENV['MPESA_SHORTCODE'])
      ->setPassKey($_ENV['MPESA_PASS_KEY']);
```

### Available Configuration Options

| Option         | Description                        | Required     |
| -------------- | ---------------------------------- | ------------ |
| environment    | 'sandbox' or 'production'          | Yes          |
| consumerKey    | Your M-Pesa API consumer key       | Yes          |
| consumerSecret | Your M-Pesa API consumer secret    | Yes          |
| shortCode      | Your M-Pesa shortcode              | Yes          |
| passKey        | Your M-Pesa passkey                | For STK Push |
| verifySSL      | Whether to verify SSL certificates | Optional     |

## Features Documentation

### STK Push

```php
$mpesa->authenticate()
      ->setPhoneNumber('2517XXXXXXXX')
      ->setAmount(100)
      ->setAccountReference('INV123')
      ->setTransactionDesc('Payment')
      ->setCallbackUrl('https://your-domain.com/callback')
      ->initiateSTKPush();
```

### B2C Payment

```php
$response = $mpesa->authenticate()
    ->setInitiatorName('your_initiator')
    ->setAmount(100)
    ->setPhoneNumber('2517XXXXXXXX')
    ->setResultUrl('https://your-domain.com/result')
    ->setTimeoutUrl('https://your-domain.com/timeout')
    ->sendB2C([
        'CommandID' => 'BusinessPayment',
        'Remarks' => 'Salary payment'
    ]);
```

### Register URLs

```php
$response = $mpesa->authenticate()
    ->registerUrls(
        'https://your-domain.com/confirmation',
        'https://your-domain.com/validation'
    );
```

## Handling Callbacks

Create a callback handler for your endpoint:

```php
// callback.php
<?php

require_once 'vendor/autoload.php';

// Get the callback data
$callbackData = file_get_contents('php://input');
$callback = json_decode($callbackData, true);

// Validate and process the callback
if (isset($callback['Body']['stkCallback'])) {
    $resultCode = $callback['Body']['stkCallback']['ResultCode'];
    $resultDesc = $callback['Body']['stkCallback']['ResultDesc'];

    if ($resultCode === 0) {
        // Payment successful
        // Update your database
        // Notify your application
    } else {
        // Payment failed
        // Handle the error
    }
}
```

## Error Handling

The SDK throws specific exceptions that you can catch and handle:

```php
use MesaSDK\PhpMpesa\Exceptions\MpesaException;

try {
    $mpesa->initiateSTKPush();
} catch (MpesaException $e) {
    // Handle M-Pesa specific errors
    error_log("M-Pesa Error: " . $e->getMessage());
} catch (Exception $e) {
    // Handle other errors
    error_log("Error: " . $e->getMessage());
}
```

## Logging

The SDK includes comprehensive logging capabilities:

```php
use MesaSDK\PhpMpesa\Logging\Logger;

// Configure custom logging
$logger = new Logger();
$logger->setLogPath('/path/to/your/logs');
$mpesa->setLogger($logger);
```

## Testing

Run the test suite:

```bash
composer test
```

## Security

- Always use HTTPS for callback URLs
- Store API credentials securely
- Validate all incoming callback data
- Use environment variables for sensitive data
- Keep the SDK updated to the latest version

## Contributing

Contributions are welcome! Please read our [Contributing Guide](CONTRIBUTING.md) for details.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Support

For support and questions, please [open an issue](https://github.com/Mesele-shishay/PhpMpesaSDK/issues) on GitHub.

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for release history.
