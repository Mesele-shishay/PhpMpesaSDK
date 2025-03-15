<?php

use MesaSDK\PhpMpesa\Config;

function getTestConfig(): Config
{
    return new Config(
        'https://apisandbox.safaricom.et',
        'test_consumer_key',
        'test_consumer_secret',
        'test_passkey',
        '174379',
        'sandbox'
    );
}