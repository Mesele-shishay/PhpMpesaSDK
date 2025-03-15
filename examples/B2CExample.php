<?php
/**
 * M-Pesa B2C (Business to Customer) Payment Example
 * 
 * This example demonstrates how to initiate a B2C payment using the M-Pesa API.
 * B2C payments are used for:
 * - Salary Payments
 * - Business Payments
 * - Promotion Payments
 * 
 * Prerequisites:
 * - Valid M-Pesa API credentials
 * - Registered initiator name
 * - Security credential
 */

require_once __DIR__ . '/../vendor/autoload.php';

use MesaSDK\PhpMpesa\Config;
use MesaSDK\PhpMpesa\Mpesa;
use MesaSDK\PhpMpesa\Exceptions\MpesaException;

// Load configuration from environment variables in production
$config = new Config();
$config->setBaseUrl("https://apisandbox.safaricom.et")
    ->setConsumerKey("7oJ7uWPDp3jwqBzGvxQOn5g8s5rPwJ3qfXvsxwHyAknxAAxi")
    ->setConsumerSecret("zEvvR7yTpNYG1DoH31MKOYOzh0iZ9kdXAK1andjjrqXdnJMTbiUMhnnz5Qf12oNC")
    ->setEnvironment('sandbox')
    ->setVerifySSL(false);  // Note: Always use true in production

// Initialize M-Pesa client
$mpesa = new Mpesa($config);

try {
    // 1. Authenticate with M-Pesa API
    $mpesa->authenticate();

    // 2. Set up B2C payment parameters
    $params = [
        'initiatorName' => 'apitest',  // Your registered initiator name
        'securityCredential' => 'lMhf0UqE4ydeEDwpUskmPgkNDZnA6NLi7z3T1TQuWCkH3/ScW8pRRnobq/AcwFvbC961+zDMgOEYGm8Oivb7L/7Y9ED3lhR7pJvnH8B1wYis5ifdeeWI6XE2NSq8X1Tc7QB9Dg8SlPEud3tgloB2DlT+JIv3ebIl/J/8ihGVrq499bt1pz/EA2nzkCtGeHRNbEDxkqkEnbioV0OM//0bv4K++XyV6jUFlIIgkDkmcK6aOU8mPBHs2um9aP+Y+nTJaa6uHDudRFg0+3G6gt1zRCPs8AYbts2IebseBGfZKv5K6Lqk9/W8657gEkrDZE8Mi78MVianqHdY/8d6D9KKhw==',
        'commandId' => 'BusinessPayment',  // Options: SalaryPayment, BusinessPayment, PromotionPayment
        'amount' => 10,
        'partyA' => '1020',  // Your shortcode
        'partyB' => '251700100150',  // Recipient phone number
        'remarks' => 'Bonus payment',  // Payment description
        'occasion' => 'StallOwner',  // Optional reference
        'queueTimeOutURL' => 'https://testt.tugza.tech/',  // Timeout callback URL
        'resultURL' => 'https://testt.tugza.tech/'  // Success callback URL
    ];

    // 3. Initiate the B2C payment
    $result = $mpesa->b2c(
        $params['initiatorName'],
        $params['securityCredential'],
        $params['commandId'],
        $params['amount'],
        $params['partyA'],
        $params['partyB'],
        $params['remarks'],
        $params['occasion'],
        $params['queueTimeOutURL'],
        $params['resultURL']
    );

    // 4. Handle the response
    if ($result && $result->getResponseMessage()) {
        echo "✅ B2C payment initiated successfully!\n";
        echo "Response: \n";
        print_r($result->getResponseMessage());

        // Store these details in your database for reconciliation
        // $conversationId = $result->getConversationId();
        // $originatorConversationId = $result->getOriginatorConversationId();
    }

} catch (MpesaException $e) {
    // Handle M-Pesa specific errors
    echo "❌ M-Pesa Error:\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "Code: " . $e->getCode() . "\n";

    // Log the error for debugging
    error_log("M-Pesa B2C Error: " . $e->getMessage());
} catch (Exception $e) {
    // Handle unexpected errors
    echo "❌ Unexpected Error: " . $e->getMessage() . "\n";
    error_log("Unexpected B2C Error: " . $e->getMessage());
}

/**
 * Next Steps:
 * 1. Implement callback handling for resultURL and queueTimeOutURL
 * 2. Store transaction details in your database
 * 3. Implement transaction status query
 * 4. Add proper logging and monitoring
 * 
 * Note: In production:
 * - Use environment variables for credentials
 * - Enable SSL verification
 * - Implement proper error handling and logging
 * - Store and track all transaction details
 */