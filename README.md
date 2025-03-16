# PHP M-Pesa SDK

A comprehensive PHP SDK for integrating with the M-Pesa API in Ethiopia. This SDK provides a simple and elegant way to interact with M-Pesa's payment services, featuring a modern fluent interface design and robust error handling.

## Features

- 🚀 Modern Fluent Interface Design
- 💳 Complete M-Pesa API Integration
  - STK Push (USSD Push)
  - Business to Customer (B2C) Payments
  - Customer to Business (C2B) Payments and Simulation
  - URL Registration and Management
  - Transaction Status Queries
- 🔄 Type-Safe Response Models
- 🛡️ Robust Error Handling and Validation
- 📝 Comprehensive Logging
- 🔒 Secure by Default
- ✨ PSR-4 Compliant
- 📚 Extensive Documentation
- 🧪 Unit Tests

## Requirements

- PHP 7.4 or higher
- Composer
- Valid M-Pesa API credentials
- HTTPS enabled server for callbacks

## Installation

Install the package via composer:

```bash
composer require mesasdk/php-mpesa
```

## Quick Start

```php
use MesaSDK\PhpMpesa\Config;
use MesaSDK\PhpMpesa\Mpesa;

// Initialize configuration
$config = new Config();
$config->setBaseUrl("https://apisandbox.safaricom.et")
    ->setConsumerKey("your_consumer_key")
    ->setConsumerSecret("your_consumer_secret")
    ->setEnvironment('sandbox')
    ->setShortcode('174379')
    ->setPasskey('your_passkey');

// Create M-Pesa instance
$mpesa = new Mpesa($config);

// Example: Simulate C2B Payment
try {
    $response = $mpesa->authenticate()
        ->setC2BAmount(110.00)
        ->setC2BMsisdn('251945628580')
        ->setC2BBillRefNumber('091091')
        ->executeC2BSimulation();

    if ($response['ResponseCode'] === '0') {
        echo "Transaction initiated successfully!";
    }
} catch (MpesaException $e) {
    echo "Error: " . $e->getMessage();
}
```

## Detailed Usage

### Configuration

The SDK supports both sandbox and production environments. Always start with sandbox for testing:

```php
$config = new Config();
$config->setBaseUrl("https://apisandbox.safaricom.et")
    ->setConsumerKey("your_consumer_key")
    ->setConsumerSecret("your_consumer_secret")
    ->setEnvironment('sandbox')  // Use 'production' for live environment
    ->setShortcode('174379')
    ->setPasskey('your_passkey')
    ->setVerifySSL(true);       // Always true in production

$mpesa = new Mpesa($config);
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
      ->ussdPush();
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

### C2B Simulation

#### Using Fluent Interface (Recommended)

```php
$response = $mpesa->authenticate()
    ->setC2BAmount(110.00)                // Set amount
    ->setC2BMsisdn('251945628580')       // Set customer phone number
    ->setC2BBillRefNumber('091091')      // Set bill reference number
    ->executeC2BSimulation();            // Execute the simulation

// Handle the response
if (isset($response['ResponseCode']) && $response['ResponseCode'] === '0') {
    // Success
    $conversationId = $response['ConversationID'];
    $originatorConversationId = $response['OriginatorConversationID'];
    $responseDesc = $response['ResponseDescription'];
} else {
    // Handle error
}
```

#### Legacy Approach (Deprecated)

```php
$response = $mpesa->authenticate()
    ->simulateCustomerPayment(
        110.00,                // Amount
        '251945628580',       // Customer phone number
        '091091'              // Bill reference number
    );
```

### C2B Validation and Confirmation

The SDK supports M-PESA Customer to Business (C2B) Validation and Confirmation APIs. These endpoints allow you to validate and confirm transactions initiated by customers through M-PESA channels.

#### Setting Up C2B Endpoints

First, register your validation and confirmation URLs:

```php
$mpesa = new Mpesa([
    'consumer_key' => 'your_consumer_key',
    'consumer_secret' => 'your_consumer_secret',
    'environment' => 'sandbox',
    'shortcode' => '174379',
    'key' => 'your_passkey'
]);

$response = $mpesa->registerUrls(
    'https://your-domain.com/mpesa/confirm',
    'https://your-domain.com/mpesa/validate'
);
```

#### Implementing Validation Endpoint

The validation endpoint allows you to verify incoming payment requests before they are processed:

```php
// In your validation endpoint handler
$request = json_decode(file_get_contents('php://input'), true);

try {
    $response = $mpesa->handleValidation($request);
    header('Content-Type: application/json');
    echo json_encode($response);
} catch (Exception $e) {
    // Handle error
}
```

#### Implementing Confirmation Endpoint

The confirmation endpoint receives notifications for completed transactions:

```php
// In your confirmation endpoint handler
$request = json_decode(file_get_contents('php://input'), true);

try {
    $response = $mpesa->handleConfirmation($request);
    header('Content-Type: application/json');
    echo json_encode($response);
} catch (Exception $e) {
    // Handle error
}
```

#### Validation Response Codes

The validation endpoint may return the following response codes:

- `0`: Transaction accepted
- `C2B00011`: Invalid MSISDN
- `C2B00012`: Invalid Account Number
- `C2B00013`: Invalid Amount
- `C2B00014`: Invalid KYC Details
- `C2B00015`: Invalid Shortcode
- `C2B00016`: Other Error

#### Example Implementation

See the complete example implementation in `examples/c2b_validation_example.php`.

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
    $mpesa->ussdPush();
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

## Response Models

The SDK provides type-safe response models for all API responses. These models make it easier to work with API responses and provide better IDE support.

### Base Response Model

All response models extend the `BaseResponse` class which provides common functionality:

```php
$response->isSuccessful();                    // Check if request was successful
$response->getResponseCode();                 // Get response code
$response->getResponseDescription();          // Get response description
$response->getConversationId();              // Get conversation ID
$response->getOriginatorConversationId();    // Get originator conversation ID
$response->toArray();                        // Convert response to array
```

### C2B Simulation Response

The `C2BSimulationResponse` model provides access to C2B simulation specific fields:

```php
/** @var C2BSimulationResponse $response */
$response = $mpesa
    ->setC2BAmount(110.00)
    ->setC2BMsisdn('251945628580')
    ->setC2BBillRefNumber('091091')
    ->executeC2BSimulation();

if ($response->isSuccessful()) {
    $customerMessage = $response->getCustomerMessage();
    $merchantRequestId = $response->getMerchantRequestId();
    $checkoutRequestId = $response->getCheckoutRequestId();
}
```

### C2B Validation Response

The `C2BValidationResponse` model provides access to validation specific fields:

```php
/** @var C2BValidationResponse $response */
$response = $mpesa->handleValidation($request);

$thirdPartyTransId = $response->getThirdPartyTransId();
$transactionDetails = $response->getTransactionDetails();
$specificDetail = $response->getTransactionDetail('key', 'default');
```

### Working with Response Models

All response models implement:

- `JsonSerializable` for easy JSON encoding
- `fromArray()` static constructor for creating instances from API responses
- `toArray()` method for converting back to arrays
- Type-safe getters for all properties
- Null-safe access to optional fields

Example usage with type checking:

```php
use MesaSDK\PhpMpesa\Models\C2BSimulationResponse;

/** @var C2BSimulationResponse $response */
$response = $mpesa->executeC2BSimulation();

if ($response->isSuccessful()) {
    // All these methods provide type-safe access to response data
    $conversationId = $response->getConversationId();
    $merchantRequestId = $response->getMerchantRequestId();
    $checkoutRequestId = $response->getCheckoutRequestId();

    // Convert to array if needed
    $responseArray = $response->toArray();
}
```

# M-PESA Account Balance API Implementation

This implementation provides a simple way to interact with the M-PESA Account Balance API. It allows businesses to programmatically check the balance of their M-PESA accounts in real-time.

## Features

- Check M-PESA account balance
- Parse balance information for multiple accounts
- Error handling and validation
- Asynchronous API support with callback URLs

## Requirements

- PHP 7.4 or higher
- Curl extension enabled
- Valid M-PESA API credentials
- Active internet connection

## Installation

1. Clone this repository
2. Install dependencies:

```bash
composer install
```

## Configuration

Before using the API, you need to set up your M-PESA API credentials:

1. Initiator name
2. Security credential
3. Party A (organization shortcode)
4. Queue timeout URL
5. Result URL
6. Bearer token

## Usage

Here's a basic example of how to use the Account Balance API:

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use MPesa\AccountBalance;

$mpesa = new AccountBalance(
    'your_initiator',
    'your_security_credential',
    'your_party_a',
    'https://your-domain.com/timeout',
    'https://your-domain.com/result'
);

try {
    $response = $mpesa->checkBalance('your_bearer_token');
    // Handle the response
} catch (\Exception $e) {
    // Handle errors
}
```

See the `examples/check_balance.php` file for a complete working example.

## Response Handling

The API provides two types of responses:

1. Immediate response - Confirms if the request was accepted
2. Callback response - Contains the actual balance information

### Immediate Response Example

```json
{
  "OriginatorConversationID": "2c22-4733-b801-a1eaa3f9763c",
  "ConversationID": "AG_20240211_70101d5c7e1c4fbf514f",
  "ResponseCode": "0",
  "ResponseDescription": "Accept the service request successfully."
}
```

### Callback Response Example

```json
{
  "Result": {
    "ResultType": 0,
    "ResultCode": 0,
    "ResultDesc": "The service request is processed successfully.",
    "OriginatorConversationID": "cd88-49b1-80c9-172990525931",
    "ConversationID": "AG_20230116_7010211995599455bcb1",
    "TransactionID": "RAG0000000",
    "ResultParameters": {
      "ResultParameter": [
        {
          "Key": "AccountBalance",
          "Value": "Working Account|ETB|0.00|0.00|0.00|0.00&Utility Account|ETB|101090.00|101090.00|0.00|0.00"
        }
      ]
    }
  }
}
```

## Error Handling

The implementation includes comprehensive error handling for various scenarios:

- Invalid credentials
- Network errors
- API timeouts
- Invalid responses

Common error codes:

- `0`: Success
- `2001`: Invalid initiator information
- `404.002.01`: Resource not found
- `401.002.01`: Invalid access token
- `400.002.02`: Bad request
- `500.001.1001`: Internal server error

## Security

Remember to:

1. Never commit your security credentials to version control
2. Use environment variables for sensitive information
3. Implement proper SSL/TLS for callback URLs
4. Validate all incoming callback data

## Support

For API-related issues, please contact M-PESA support. For implementation-specific issues, please open an issue in this repository.

### Account Balance Query

The SDK supports querying account balance for M-PESA accounts. Here's how to use it:

```php
try {
    $response = $mpesa->authenticate()
        ->setSecurityCredential("your-security-credential")
        ->setAccountBalanceInitiator('apitest')
        ->setAccountBalancePartyA('1020')
        ->setAccountBalanceRemarks('Monthly balance check')
        // Optional parameters
        ->setAccountBalanceIdentifierType('4')
        ->setQueueTimeOutUrl('https://your-domain.com/timeout')
        ->setResultUrl('https://your-domain.com/result')
        ->checkAccountBalance();

    // Handle successful response
    if (isset($response['Result'])) {
        $balanceInfo = $mpesa->parseBalanceResult($response);
        foreach ($balanceInfo as $account) {
            echo sprintf(
                "Account: %s\nCurrency: %s\nAmount: %s\n\n",
                $account['account'],
                $account['currency'],
                $account['amount']
            );
        }
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

#### Account Balance Parameters

| Parameter          | Description                                                 | Required |
| ------------------ | ----------------------------------------------------------- | -------- |
| securityCredential | Your encrypted security credential                          | Yes      |
| initiator          | The name of the initiator initiating the request            | Yes      |
| partyA             | Organization/MSISDN receiving the transaction               | Yes      |
| remarks            | Comments about the transaction                              | Optional |
| identifierType     | Type of organization receiving the transaction (default: 4) | Optional |
| queueTimeOutUrl    | Timeout URL for the request                                 | Yes      |
| resultUrl          | Result URL for the request                                  | Yes      |
