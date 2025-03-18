<?php

use MesaSDK\PhpMpesa\Config;
use MesaSDK\PhpMpesa\Mpesa;

require_once __DIR__ . '/../vendor/autoload.php';

$config = new Config();

$config->setEnvironment('sandbox')
    ->setConsumerKey("QeZ1WgHxMJCngVLGbsHwMQSmZO7HHnQjGGbeSH3VaKB90fta")
    ->setVerifySSL(false)
    ->setConsumerSecret("bM7gNvNTXH7T3IPzUAIYpa4xzlENgGPC4raksDXWt2VvjcquzgD80P3G6cM01BEv");



try {
    // Initialize the CustomMpesa class with configuration
    $mpesa = new Mpesa($config);

    // Configure account balance specific parameters using fluent interface
    $response = $mpesa
        ->authenticate()
        ->setSecurityCredential("lMhf0UqE4ydeEDwpUskmPgkNDZnA6NLi7z3T1TQuWCkH3/ScW8pRRnobq/AcwFvbC961+zDMgOEYGm8Oivb7L/7Y9ED3lhR7pJvnH8B1wYis5ifdeeWI6XE2NSq8X1Tc7QB9Dg8SlPEud3tgloB2DlT+JIv3ebIl/J/8ihGVrq499bt1pz/EA2nzkCtGeHRNbEDxkqkEnbioV0OM//0bv4K++XyV6jUFlIIgkDkmcK6aOU8mPBHs2um9aP+Y+nTJaa6uHDudRFg0+3G6gt1zRCPs8AYbts2IebseBGfZKv5K6Lqk9/W8657gEkrDZE8Mi78MVianqHdY/8d6D9KKhw==")
        ->setAccountBalanceInitiator('apitest')
        ->setAccountBalancePartyA('1020')
        ->setAccountBalanceRemarks('Monthly balance check')
        // Optional parameters
        ->setAccountBalanceIdentifierType('4')
        ->setAccountBalanceRemarks('Monthly balance check')
        ->setQueueTimeOutUrl('https://your-domain.com/timeout')
        ->setResultUrl('https://your-domain.com/result')
        // Execute the balance check
        ->checkAccountBalance();

    echo "API Response:\n";
    echo json_encode($response, JSON_PRETTY_PRINT) . "\n\n";

    // // If this is a result callback
    // if (isset($response['Result'])) {
    //     $balanceInfo = $mpesa->parseBalanceResult($response);
    //     echo "Parsed Balance Information:\n";
    //     foreach ($balanceInfo as $account) {
    //         echo sprintf(
    //             "Account: %s\nCurrency: %s\nAmount: %s\n\n",
    //             $account['account'],
    //             $account['currency'],
    //             $account['amount']
    //         );
    //     }
    // }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}