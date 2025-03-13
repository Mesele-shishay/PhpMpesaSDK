# M-Pesa SDK PHP

This is a PHP SDK for integrating with Safaricom's M-Pesa API using a fluent approach. The SDK provides a simple and elegant way to interact with various M-Pesa services.

## Features

- Simple, fluent interface for M-Pesa integration
- Automatic environment-based URL configuration
- Comprehensive input validation
- Proper error handling
- Secure by default (HTTPS required for callbacks)
- Environment variable support
- Flexible configuration options
- Multiple configuration methods
- Professional namespace structure
- C2B (Customer to Business) Integration
- URL Registration Support
- Authentication Management
- Robust Exception Handling

## Installation

```sh
composer require mesele/mpesa-sdk
```

## Namespace

The SDK uses the `MesaSDK\PhpMpesa` namespace:

```php
use MesaSDK\PhpMpesa\Mpesa;
use MesaSDK\PhpMpesa\Config;
```

## Configuration

The SDK provides multiple ways to configure your M-Pesa integration:

### 1. Direct Array Configuration

The simplest way to configure the SDK is by passing an array of configuration values:

```php
use MesaSDK\PhpMpesa\Mpesa;

// Configure during initialization
$mpesa = new Mpesa([
    'environment' => 'sandbox', // or 'production'
    'consumer_key' => 'your_consumer_key',
    'consumer_secret' => 'your_consumer_secret',
    'passkey' => 'your_passkey',
    'shortcode' => 'your_shortcode'
]);

// OR configure after initialization
$mpesa = new Mpesa();
$mpesa->configure([
    'environment' => 'sandbox',
    'consumer_key' => 'your_consumer_key',
    'consumer_secret' => 'your_consumer_secret',
    'passkey' => 'your_passkey',
    'shortcode' => 'your_shortcode'
]);
```

### 2. Using Environment Variables (.env)

Create a `.env` file in your project root:

```env
MPESA_ENVIRONMENT=sandbox     # or production
MPESA_CONSUMER_KEY=your_consumer_key
MPESA_CONSUMER_SECRET=your_consumer_secret
MPESA_PASSKEY=your_passkey
MPESA_SHORTCODE=your_shortcode
```

Then initialize without parameters:

```php
use MesaSDK\PhpMpesa\Mpesa;

// Will automatically load from .env if file exists
$mpesa = new Mpesa();
```

### 3. Using Config Class

For more advanced configuration management:

```php
use MesaSDK\PhpMpesa\Config;
use MesaSDK\PhpMpesa\Mpesa;

$config = new Config(
    consumer_key: "your_consumer_key",
    consumer_secret: "your_consumer_secret",
    passkey: "your_passkey",
    shortcode: "your_shortcode",
    environment: "sandbox"  // Optional, defaults to 'sandbox'
);

$mpesa = new Mpesa($config);
```

### 4. Using Config Setter Methods

```php
use MesaSDK\PhpMpesa\Config;
use MesaSDK\PhpMpesa\Mpesa;

$config = new Config();
$config->setEnvironment("sandbox")
    ->setConsumerKey("your_consumer_key")
    ->setConsumerSecret("your_consumer_secret")
    ->setPasskey("your_passkey")
    ->setShortcode("your_shortcode");

$mpesa = new Mpesa($config);
```

### Configuration Parameters

All configuration methods accept the following parameters:

- `environment`: The API environment ('sandbox' or 'production')
- `consumer_key`: Your app's Consumer Key from the M-Pesa Developer Portal
- `consumer_secret`: Your app's Consumer Secret from the M-Pesa Developer Portal
- `passkey`: Your M-Pesa Passkey for generating transaction passwords
- `shortcode`: Your business Shortcode, Till Number, or Paybill Number
- `base_url`: (Optional) Custom base URL if needed

## Usage Examples

### 1. C2B Integration

```php
use MesaSDK\PhpMpesa\Mpesa;

$mpesa = new Mpesa([/* your config */]);

// Register C2B URLs
$response = $mpesa->registerUrls([
    'ValidationURL' => 'https://example.com/validation',
    'ConfirmationURL' => 'https://example.com/confirmation',
    'ResponseType' => 'Completed'
]);

// Process C2B transaction
$result = $mpesa->c2b([
    'ShortCode' => 'YOUR_SHORTCODE',
    'CommandID' => 'CustomerPayBillOnline',
    'Amount' => '100',
    'Msisdn' => '254712345678',
    'BillRefNumber' => 'INV001'
]);
```

### 2. Authentication

```php
use MesaSDK\PhpMpesa\Authentication;

$auth = new Authentication($config);
$token = $auth->generateToken();
```

### 3. STK Push

```php
$response = $mpesa->stkPush([
    'Amount' => 1,
    'PartyA' => '254712345678',
    'PartyB' => 'YOUR_SHORTCODE',
    'PhoneNumber' => '254712345678',
    'CallBackURL' => 'https://example.com/callback',
    'AccountReference' => 'CompanyXLTD',
    'TransactionDesc' => 'Payment of X'
]);
```

## Error Handling

The SDK includes comprehensive error handling:

```php
try {
    $result = $mpesa->c2b([/* params */]);
} catch (MpesaException $e) {
    // Handle M-Pesa specific errors
    echo $e->getMessage();
    echo $e->getCode();
} catch (\Exception $e) {
    // Handle general errors
    echo $e->getMessage();
}
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This project is licensed under the MIT License - see the LICENSE file for details.

```

```
