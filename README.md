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
use MesaSDK\PhpMpesa\Exceptions\MpesaException;

// Initialize configuration
$config = new Config();
$config->setBaseUrl("https://apisandbox.safaricom.et")
    ->setEnvironment('sandbox')  // Use 'production' for live environment
    ->setConsumerKey('your_consumer_key')
    ->setConsumerSecret('your_consumer_secret')
    ->setShortCode('your_shortcode')
    ->setPassKey('your_passkey')
    ->setVerifySSL(true);  // Always true in production

// Create M-Pesa instance
$mpesa = new Mpesa($config);

try {
    $response = $mpesa->authenticate()
        ->setC2BAmount(110.00)
        ->setC2BMsisdn('251945628580')
        ->setC2BBillRefNumber('091091')
        ->executeC2BSimulation();

    if ($response->isSuccessful()) {
        echo "Transaction initiated successfully!";
        echo "Response Code: " . $response->getResponseCode();
        echo "Conversation ID: " . $response->getConversationId();
    }
} catch (MpesaException $e) {
    echo "Error: " . $e->getMessage();
}
```

## Configuration

### Environment Variables

We recommend using environment variables for sensitive configuration:

```php
$config = new Config();
$config->setBaseUrl("https://apisandbox.safaricom.et")  // Add base URL
    ->setEnvironment($_ENV['MPESA_ENVIRONMENT'])
    ->setConsumerKey($_ENV['MPESA_CONSUMER_KEY'])
    ->setConsumerSecret($_ENV['MPESA_CONSUMER_SECRET'])
    ->setShortCode($_ENV['MPESA_SHORTCODE'])
    ->setPassKey($_ENV['MPESA_PASS_KEY'])
    ->setVerifySSL(true);  // Set to false only for sandbox testing
```

### Available Configuration Options

| Option         | Description                        | Required     | Notes                       |
| -------------- | ---------------------------------- | ------------ | --------------------------- |
| baseUrl        | API base URL                       | Yes          | Use sandbox URL for testing |
| environment    | 'sandbox' or 'production'          | Yes          | Start with sandbox          |
| consumerKey    | Your M-Pesa API consumer key       | Yes          | Keep secure                 |
| consumerSecret | Your M-Pesa API consumer secret    | Yes          | Keep secure                 |
| shortCode      | Your M-Pesa shortcode              | Yes          | -                           |
| passKey        | Your M-Pesa passkey                | For STK Push | -                           |
| verifySSL      | Whether to verify SSL certificates | Optional     | Always true in production   |

## Features Documentation

### STK Push

```php
try {
    $mpesa->authenticate()
        ->setPhoneNumber('2517XXXXXXXX')
        ->setAmount(100)
        ->setAccountReference('INV' . time())   // Dynamic reference
        ->setTransactionDesc('Payment for Package')
        ->setCallbackUrl('https://your-domain.com/callback');

    // For sandbox testing only
    if ($config->getEnvironment() === 'sandbox') {
        $mpesa->setTestPassword('your-test-password');
    }

    $response = $mpesa->ussdPush();

    if ($mpesa->isSuccessful()) {
        echo "Transaction Details:\n";
        echo "Merchant Request ID: " . $mpesa->getMerchantRequestID() . "\n";
        echo "Checkout Request ID: " . $mpesa->getCheckoutRequestID() . "\n";
    }
} catch (MpesaException $e) {
    echo "M-Pesa Error: " . $e->getMessage();
}
```

### B2C Payment

```php
try {
    $result = $mpesa->authenticate()
        ->setInitiatorName('your_initiator')
        ->setSecurityCredential('your_security_credential')
        ->setCommandId('BusinessPayment')  // Options: SalaryPayment, BusinessPayment, PromotionPayment
        ->setAmount(100)
        ->setPartyA('your_shortcode')
        ->setPartyB('2517XXXXXXXX')
        ->setRemarks('Payment description')
        ->setOccasion('Optional reference')
        ->setQueueTimeOutUrl('https://your-domain.com/timeout')
        ->setResultUrl('https://your-domain.com/result')
        ->b2c();

    if ($result && $result->getResponseMessage()) {
        echo "B2C payment initiated successfully!";
        // Store conversation IDs for reconciliation
        $conversationId = $result->getConversationId();
        $originatorConversationId = $result->getOriginatorConversationId();
    }
} catch (MpesaException $e) {
    echo "Error: " . $e->getMessage();
}
```

### C2B Simulation

```php
use MesaSDK\PhpMpesa\Models\C2BSimulationResponse;

try {
    /** @var C2BSimulationResponse $response */
    $response = $mpesa->authenticate()
        ->setC2BAmount(110.00)
        ->setC2BMsisdn('251945628580')
        ->setC2BBillRefNumber('091091')
        ->executeC2BSimulation();

    if ($response->isSuccessful()) {
        echo "C2B payment simulation initiated successfully!";
        echo "Response Code: " . $response->getResponseCode();
        echo "Conversation ID: " . $response->getConversationId();
        echo "Customer Message: " . $response->getCustomerMessage();
    } else {
        echo "C2B payment simulation failed: " . $response->getResponseDescription();
    }
} catch (MpesaException $e) {
    echo "Error: " . $e->getMessage();
}
```

### Account Balance Query

```php
try {
    $response = $mpesa->authenticate()
        ->setSecurityCredential("your-security-credential")
        ->setAccountBalanceInitiator('your_initiator')
        ->setAccountBalancePartyA('your_shortcode')
        ->setAccountBalanceRemarks('Balance check')
        ->setAccountBalanceIdentifierType('4')
        ->setQueueTimeOutUrl('https://your-domain.com/timeout')
        ->setResultUrl('https://your-domain.com/result')
        ->checkAccountBalance();

    // Handle immediate response
    if ($response['ResponseCode'] === '0') {
        echo "Balance query initiated successfully";
    }

    // If this is a result callback
    if (isset($response['Result'])) {
        $balanceInfo = $mpesa->parseBalanceResult($response);
        foreach ($balanceInfo as $account) {
            echo sprintf(
                "Account: %s\nCurrency: %s\nAmount: %s\n",
                $account['account'],
                $account['currency'],
                $account['amount']
            );
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

### C2B Validation and Confirmation

First, register your validation and confirmation URLs:

```php
$response = $mpesa->authenticate()
    ->registerUrls(
        'https://your-domain.com/mpesa/confirm',
        'https://your-domain.com/mpesa/validate'
    );
```

#### Validation Endpoint

```php
<?php
require_once 'vendor/autoload.php';

try {
    // Get the callback data
    $callbackData = file_get_contents('php://input');
    $callback = json_decode($callbackData, true);

    // Validate and process the callback
    if (isset($callback['Body']['stkCallback'])) {
        $resultCode = $callback['Body']['stkCallback']['ResultCode'];
        $resultDesc = $callback['Body']['stkCallback']['ResultDesc'];

        if ($resultCode === 0) {
            // Payment successful
            $amount = $callback['Body']['stkCallback']['CallbackMetadata']['Item'][0]['Value'];
            $mpesaReceiptNumber = $callback['Body']['stkCallback']['CallbackMetadata']['Item'][1]['Value'];
            $transactionDate = $callback['Body']['stkCallback']['CallbackMetadata']['Item'][3]['Value'];
            $phoneNumber = $callback['Body']['stkCallback']['CallbackMetadata']['Item'][4]['Value'];

            // Store transaction details in your database
            // Update order status
            // Send confirmation to customer

            // Return success response
            http_response_code(200);
            echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Success']);
        } else {
            // Payment failed
            error_log("Payment failed: " . $resultDesc);
            // Handle the error (notify customer, update order status, etc.)
        }
    }
} catch (Exception $e) {
    error_log("Callback Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal Server Error']);
}
```

#### Confirmation Endpoint

```php
// In your confirmation endpoint handler
try {
    $request = json_decode(file_get_contents('php://input'), true);
    $response = $mpesa->handleConfirmation($request);

    // Store transaction details
    $transactionId = $response->getTransactionId();
    $amount = $response->getAmount();
    $phoneNumber = $response->getPhoneNumber();

    header('Content-Type: application/json');
    echo json_encode($response);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
```

#### Response Models

The SDK provides type-safe response models for all API responses:

```php
/** @var C2BSimulationResponse $response */
$response = $mpesa->executeC2BSimulation();

if ($response->isSuccessful()) {
    // Type-safe access to response data
    $conversationId = $response->getConversationId();
    $merchantRequestId = $response->getMerchantRequestId();
    $checkoutRequestId = $response->getCheckoutRequestId();

    // Convert to array if needed
    $responseArray = $response->toArray();
}

```

## Security Best Practices

1. Environment Variables:

   - Store all sensitive credentials in environment variables
   - Never commit credentials to version control
   - Use .env files for local development

2. SSL/TLS:

   - Always use HTTPS for callback URLs
   - Set verifySSL to true in production
   - Keep SSL certificates up to date

3. Error Handling:

   - Implement comprehensive error logging
   - Never expose sensitive error details to users
   - Monitor failed transactions

4. Data Validation:
   - Validate all incoming callback data
   - Implement request signing where possible
   - Use type-safe response models

## Handling Callbacks

Create a callback handler for your endpoint:

```php
<?php
require_once 'vendor/autoload.php';

try {
    // Get the callback data
    $callbackData = file_get_contents('php://input');
    $callback = json_decode($callbackData, true);

    // Validate and process the callback
    if (isset($callback['Body']['stkCallback'])) {
        $resultCode = $callback['Body']['stkCallback']['ResultCode'];
        $resultDesc = $callback['Body']['stkCallback']['ResultDesc'];

        if ($resultCode === 0) {
            // Payment successful
            $amount = $callback['Body']['stkCallback']['CallbackMetadata']['Item'][0]['Value'];
            $mpesaReceiptNumber = $callback['Body']['stkCallback']['CallbackMetadata']['Item'][1]['Value'];
            $transactionDate = $callback['Body']['stkCallback']['CallbackMetadata']['Item'][3]['Value'];
            $phoneNumber = $callback['Body']['stkCallback']['CallbackMetadata']['Item'][4]['Value'];

            // Store transaction details in your database
            // Update order status
            // Send confirmation to customer

            // Return success response
            http_response_code(200);
            echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Success']);
        } else {
            // Payment failed
            error_log("Payment failed: " . $resultDesc);
            // Handle the error (notify customer, update order status, etc.)
        }
    }
} catch (Exception $e) {
    error_log("Callback Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal Server Error']);
}
```

## Error Handling

The SDK provides comprehensive error handling through custom exceptions:

```php
use MesaSDK\PhpMpesa\Exceptions\MpesaException;
use MesaSDK\PhpMpesa\Exceptions\ConfigurationException;
use MesaSDK\PhpMpesa\Exceptions\ValidationException;

try {
    $response = $mpesa->authenticate()
        ->setPhoneNumber('2517XXXXXXXX')
        ->setAmount(100)
        ->ussdPush();
} catch (ConfigurationException $e) {
    // Handle configuration errors (invalid credentials, missing required fields)
    error_log("Configuration Error: " . $e->getMessage());
    echo "Please check your M-Pesa configuration.";
} catch (ValidationException $e) {
    // Handle validation errors (invalid phone number, amount, etc.)
    error_log("Validation Error: " . $e->getMessage());
    echo "Please check your input data.";
} catch (MpesaException $e) {
    // Handle M-Pesa API specific errors
    error_log("M-Pesa Error: " . $e->getMessage());
    error_log("Error Code: " . $e->getCode());
    echo "Transaction failed. Please try again later.";
} catch (Exception $e) {
    // Handle unexpected errors
    error_log("Unexpected Error: " . $e->getMessage());
    echo "An unexpected error occurred.";
}
```

## Logging

The SDK includes comprehensive logging capabilities:

```php
use MesaSDK\PhpMpesa\Logging\Logger;

// Configure custom logging
$logger = new Logger();

// Set custom log path
$logger->setLogPath('/path/to/your/logs');

// Enable debug logging
$logger->setDebug(true);

// Add logger to M-Pesa instance
$mpesa->setLogger($logger);

// Logs will now include:
// - API requests and responses
// - Authentication attempts
// - Transaction details
// - Error messages and stack traces
```

### Log File Example

````log
[2024-03-18 10:15:30] mpesa.INFO: Initiating authentication request
[2024-03-18 10:15:31] mpesa.DEBUG: Authentication successful. Token: abc...xyz
[2024-03-18 10:15:32] mpesa.INFO: STK push request initiated for phone: 2517XXXXXXXX
[2024-03-18 10:15:33] mpesa.DEBUG: Response received: {"ResultCode": "0", "ResultDesc": "Success"}


### Base Response Model

All response models extend the `BaseResponse` class which provides common functionality:

```php
$response->isSuccessful();                    // Check if request was successful
$response->getResponseCode();                 // Get response code
$response->getResponseDescription();          // Get response description
$response->getConversationId();              // Get conversation ID
$response->getOriginatorConversationId();    // Get originator conversation ID
$response->toArray();                        // Convert response to array
````

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

````

## Testing

Run the test suite:

```bash
composer test
````

The SDK includes comprehensive tests:

- Unit tests for all core functionality
- Integration tests for API endpoints
- Mock responses for offline testing
- Test coverage reports

## Contributing

We welcome contributions! Please follow these steps:

1. Fork the repository
2. Create a feature branch
3. Write your changes
4. Write tests for your changes
5. Run the tests
6. Submit a pull request

Please read our [Contributing Guide](CONTRIBUTING.md) for details.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Support

For support and questions:

- [Open an issue](https://github.com/Mesele-shishay/PhpMpesaSDK/issues) on GitHub
- Check our [documentation](https://mesele-shishay.github.io/PhpMpesaSDK/)
- Join our [community forum](https://github.com/Mesele-shishay/PhpMpesaSDK/discussions)

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for release history.

## Response Models

The SDK provides type-safe response models for all API responses. These models make it easier to work with API responses and provide better IDE support.
