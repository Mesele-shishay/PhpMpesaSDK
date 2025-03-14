# M-PESA Account Balance API Implementation

This implementation provides a simple way to interact with the M-PESA Account Balance API. It allows businesses to programmatically check the balance of their M-PESA accounts in real-time.

## Features

- Query M-PESA account balance
- Handle asynchronous responses
- Parse account balance results
- Error handling
- Type-safe implementation
- Trait-based implementation for easy integration

## Requirements

- PHP 7.4 or higher
- curl extension
- json extension

## Installation

1. Add this package to your project:

```bash
composer require your-vendor/mpesa-account-balance
```

2. Include the autoloader in your PHP script:

```php
require_once 'vendor/autoload.php';
```

## Usage

### Basic Usage

```php
use MPesa\MPesa;

// Initialize with your credentials
$mpesa = new MPesa(
    'your-initiator-name',
    'your-security-credential',
    'your-party-a',
    'https://your-domain.com/timeout',
    'https://your-domain.com/result'
);

// Query account balance
try {
    $response = $mpesa->queryAccountBalance('your-bearer-token');
    print_r($response);
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

### Using the Trait in Your Own Class

You can also use the trait in your own class:

```php
use MPesa\Traits\HasAccountBalance;

class YourClass
{
    use HasAccountBalance;

    public function __construct()
    {
        $this->initializeAccountBalance(
            'your-initiator-name',
            'your-security-credential',
            'your-party-a',
            'https://your-domain.com/timeout',
            'https://your-domain.com/result'
        );
    }
}
```

### Handling Callback Results

When M-PESA sends the final result to your ResultURL, you can parse it using:

```php
$balances = $mpesa->parseBalanceResult($callbackResult);
print_r($balances);
```

## Response Format

The initial response will be in the format:

```json
{
  "OriginatorConversationID": "2c22-4733-b801-a1eaa3f9763c",
  "ConversationID": "AG_20240211_70101d5c7e1c4fbf514f",
  "ResponseCode": "0",
  "ResponseDescription": "Accept the service request successfully."
}
```

The callback result will be parsed into an array of account balances:

```php
[
    [
        'accountName' => 'Working Account',
        'currency' => 'ETB',
        'availableBalance' => '0.00',
        'reservedAmount' => '0.00',
        'unClearedBalance' => '0.00',
        'totalBalance' => '0.00'
    ],
    // ... more accounts
]
```

## Error Handling

The implementation throws exceptions for:

- cURL errors
- API errors (HTTP 4xx, 5xx responses)
- Invalid response formats
- Missing configuration

## Security

- Never commit your security credentials to version control
- Use environment variables or a secure configuration management system
- Ensure your callback URLs use HTTPS
- Validate all incoming callback data

## License

MIT License

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

```

```
