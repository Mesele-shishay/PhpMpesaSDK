# PHP M-Pesa Integration SDK

A PHP SDK for seamless integration with Safaricom's M-Pesa payment services. This package provides an easy-to-use interface for implementing M-Pesa payment functionalities in your PHP applications.

## Features

✨ Simple and intuitive API with fluent interface
✨ Comprehensive M-Pesa operations support
✨ Robust error handling
✨ Type-safe implementation
✨ Well-documented codebase
✨ Production-ready security measures

## Requirements

- PHP >= 7.4
- Composer
- GuzzleHTTP ^7.0
- vlucas/phpdotenv ^5.6
- M-Pesa API credentials (Consumer Key and Consumer Secret)
- SSL enabled web server (for production)

## Quick Start

### 1. Installation

```bash
composer require mesa-sdk/php-mpesa-sdk
```

### 2. Basic Setup

Create a `.env` file in your project root:

```env
MPESA_ENVIRONMENT=sandbox  # or production
MPESA_CONSUMER_KEY=your_consumer_key
MPESA_CONSUMER_SECRET=your_consumer_secret
MPESA_SHORTCODE=your_shortcode
MPESA_BASE_URL=https://sandbox.safaricom.co.ke
```

### 3. Initialize the SDK

```php
use MesaSDK\PhpMpesa\Config;
use MesaSDK\PhpMpesa\Mpesa;

// Create configuration with fluent interface
$config = new Config();
$config->setBaseUrl(getenv('MPESA_BASE_URL'))
    ->setConsumerKey(getenv('MPESA_CONSUMER_KEY'))
    ->setConsumerSecret(getenv('MPESA_CONSUMER_SECRET'))
    ->setEnvironment(getenv('MPESA_ENVIRONMENT'))
    ->setShortCode(getenv('MPESA_SHORTCODE'))
    ->setVerifySSL(true);  // Set to false for sandbox testing

// Initialize Mpesa
$mpesa = new Mpesa($config);

// Optional: Configure additional settings if needed
$mpesa->setPassKey('your-pass-key')  // For STK Push
    ->setInitiatorPassword('your-password')  // For B2C/B2B
    ->setTimeout(30);  // Request timeout in seconds
```

## Common Use Cases

### 1. STK Push (Lipa Na M-Pesa Online)

```php
try {
    $response = $mpesa->authenticate()
        ->setPhoneNumber('254712345678')
        ->setAmount(100)
        ->setAccountReference('INV001')
        ->setTransactionDesc('Payment for service')
        ->setCallbackUrl('https://your-domain.com/callback')
        ->initiateSTKPush();

    // Handle successful initiation
    $checkoutRequestId = $response['CheckoutRequestID'];

} catch (MpesaException $e) {
    // Handle M-Pesa specific errors
    echo $e->getMessage();
} catch (\Exception $e) {
    // Handle general errors
    echo $e->getMessage();
}
```

### 2. B2C Payment (Business to Customer)

```php
try {
    $response = $mpesa->authenticate()  // Always authenticate first
        ->setInitiatorName('John Doe')
        ->setSecurityCredential('your-credential')
        ->setCommandId('SalaryPayment')
        ->setAmount(1000)
        ->setPartyA('600000')
        ->setPartyB('254712345678')
        ->setRemarks('Salary payment')
        ->setOccasion('July Salary')
        ->setTimeoutUrl('https://your-domain.com/timeout')
        ->setResultUrl('https://your-domain.com/result')
        ->sendB2C();

    // Handle successful initiation
    $conversationId = $response['ConversationID'];

} catch (MpesaException $e) {
    echo $e->getMessage();
}
```

### 3. Register URLs for Callbacks

```php
try {
    $response = $mpesa->register(
        shortCode: '600000',
        responseType: 'Completed',
        confirmationUrl: 'https://your-domain.com/confirmation',
        validationUrl: 'https://your-domain.com/validation'
    );

    // Handle successful registration
    echo "URLs registered successfully";

} catch (MpesaException $e) {
    echo $e->getMessage();
}
```

## Handling Callbacks

Create callback endpoints in your application:

```php
// callback.php
<?php

$callbackData = file_get_contents('php://input');
$response = json_decode($callbackData, true);

// Validate the transaction
if ($response['ResultCode'] === 0) {
    // Transaction successful
    $amount = $response['Amount'];
    $mpesaReceiptNumber = $response['MpesaReceiptNumber'];
    $phoneNumber = $response['PhoneNumber'];

    // Update your database
    // Send confirmation to user
    // etc.
} else {
    // Handle failed transaction
    $errorMessage = $response['ResultDesc'];
}

// Always respond to M-Pesa
header('Content-Type: application/json');
echo json_encode([
    "ResultCode" => 0,
    "ResultDesc" => "Confirmation received successfully"
]);
```

## Security Best Practices

1. **Environment Variables**

   - Never hardcode credentials
   - Use .env files or secure secrets management
   - Keep .env file in .gitignore

2. **Production Settings**

   ```php
   $config->setEnvironment('production')
          ->setSecureSSL(true);
   ```

3. **Callback Security**
   - Validate all incoming data
   - Use HTTPS for callbacks
   - Implement IP whitelisting
   - Log all transactions

## Error Handling

```php
try {
    // M-Pesa operations
} catch (MpesaException $e) {
    // Log the error
    error_log($e->getMessage());

    // Get detailed error info
    $errorCode = $e->getCode();
    $errorMessage = $e->getMessage();
    $errorResponse = $e->getResponse();

    // Handle the error appropriately
    // e.g., notify admin, retry transaction, etc.
}
```

## Testing

For sandbox testing, use these test credentials:

- Phone Number: 254708374149
- Amount: Any amount
- Shortcode: 174379

```php
// Set sandbox environment
$config->setEnvironment('sandbox');
```

## Support

- [Create an Issue](https://github.com/yourusername/php-mpesa-sdk/issues)
- [Documentation](https://your-documentation-url.com)
- Email: your.support@email.com

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Directory Structure

```
├── examples/
│   ├── AuthExample.php
│   ├── B2CExample.php
│   ├── B2CCallbackExample.php
│   ├── RegisterUrlExample.php
│   ├── STKPushExample.php
│   └── STKPushCallbackExample.php
├── vendor/
├── composer.json
└── README.md
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

[Add your license information here]

## Support

For support and queries, please [create an issue](link-to-issues) or contact [your-contact-information].

## Disclaimer

This is example code for demonstration purposes. Ensure proper testing and security measures before using in a production environment.

# M-PESA SDK for PHP

A comprehensive PHP SDK for Safaricom's M-PESA API integration, featuring a fluent interface, robust error handling, and comprehensive documentation.

## Features

- Account Balance Query
- Handle asynchronous responses
- Parse account balance results
- Robust error handling
- Type-safe implementation
- PSR-4 compliant
- Comprehensive test coverage
- Modern PHP practices

## Requirements

- PHP >= 7.4
- GuzzleHTTP ^7.0
- vlucas/phpdotenv ^5.6

## Installation

Install the package via Composer:
