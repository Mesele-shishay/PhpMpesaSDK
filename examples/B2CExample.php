<?php

require_once __DIR__ . '/../vendor/autoload.php';

use MesaSDK\PhpMpesa\Config;
use MesaSDK\PhpMpesa\Mpesa;
use MesaSDK\PhpMpesa\Exceptions\MpesaException;

// Initialize configuration
$config = new Config(
);

$config->setBaseUrl("https://apisandbox.safaricom.et")
    ->setConsumerKey("7oJ7uWPDp3jwqBzGvxQOn5g8s5rPwJ3qfXvsxwHyAknxAAxi")
    ->setConsumerSecret("zEvvR7yTpNYG1DoH31MKOYOzh0iZ9kdXAK1andjjrqXdnJMTbiUMhnnz5Qf12oNC")
    ->setEnvironment('sandbox')
    ->setVerifySSL(false);


// Create Mpesa instance
$mpesa = new Mpesa($config);

try {
    // Authenticate first
    $mpesa->authenticate();

    // Use the b2c method with positional arguments
    $result2 = $mpesa->b2c(
        'apitest',                // initiatorName
        'lMhf0UqE4ydeEDwpUskmPgkNDZnA6NLi7z3T1TQuWCkH3/ScW8pRRnobq/AcwFvbC961+zDMgOEYGm8Oivb7L/7Y9ED3lhR7pJvnH8B1wYis5ifdeeWI6XE2NSq8X1Tc7QB9Dg8SlPEud3tgloB2DlT+JIv3ebIl/J/8ihGVrq499bt1pz/EA2nzkCtGeHRNbEDxkqkEnbioV0OM//0bv4K++XyV6jUFlIIgkDkmcK6aOU8mPBHs2um9aP+Y+nTJaa6uHDudRFg0+3G6gt1zRCPs8AYbts2IebseBGfZKv5K6Lqk9/W8657gEkrDZE8Mi78MVianqHdY/8d6D9KKhw==', // securityCredential
        'BusinessPayment',          // commandId
        10,                   // amount
        '1020',               // partyA
        '251700100150',          // partyB
        'Bonus payment',          // remarks
        'StallOwner',      // occasion
        'https://testt.tugza.tech/', // queueTimeOutURL
        'https://testt.tugza.tech/'   // resultURL
    );

    print_r($result2->getResponseMessage());

} catch (MpesaException $e) {
    echo "Error occurred: " . $e->getMessage() . "\n";
    echo "Error code: " . $e->getCode() . "\n";
} catch (Exception $e) {
    echo "Unexpected error: " . $e->getMessage() . "\n";
}