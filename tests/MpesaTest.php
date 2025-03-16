<?php

use MesaSDK\PhpMpesa\Mpesa;
use MesaSDK\PhpMpesa\Config;
use MesaSDK\PhpMpesa\Responses\MpesaResponse;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

beforeEach(function () {
    $this->config = new Config(
        'https://apisandbox.safaricom.et',
        'test_consumer_key',
        'test_consumer_secret',
        'test_passkey',
        '174379',
        'sandbox'
    );

    // Mock successful authentication response
    $mock = new MockHandler([
        new Response(200, [], json_encode([
            'access_token' => 'test-token',
            'expires_in' => '3599'
        ]))
    ]);

    $handlerStack = HandlerStack::create($mock);
    $this->client = new Client(['handler' => $handlerStack]);
});

test('mpesa can be instantiated', function () {
    $mpesa = new Mpesa($this->config);
    expect($mpesa)->toBeInstanceOf(Mpesa::class);
});

test('mpesa can initiate stk push', function () {
    $mock = new MockHandler([
        // Authentication response
        new Response(200, [], json_encode([
            'access_token' => 'test-token',
            'expires_in' => '3599'
        ])),
        // STK Push response
        new Response(200, [], json_encode([
            'MerchantRequestID' => 'test-merchant-request',
            'CheckoutRequestID' => 'test-checkout-request',
            'ResponseCode' => '0',
            'ResponseDescription' => 'Success. Request accepted for processing',
            'CustomerMessage' => 'Success. Request accepted for processing'
        ]))
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $mpesa = new Mpesa($this->config);
    $mpesa->setClient($client);
    $mpesa->authenticate();
    $mpesa->setCallbackUrl('https://example.com/callback');

    $response = $mpesa->stkPush(1.0, '251712345678', 'TEST123', 'Test Payment');

    expect($response)->toBeArray()
        ->and($response)->toHaveKey('ResponseCode')
        ->and($response['ResponseCode'])->toBe('0');
});

test('mpesa can check stk push status', function () {
    $mock = new MockHandler([
        // Authentication response
        new Response(200, [], json_encode([
            'access_token' => 'test-token',
            'expires_in' => '3599'
        ])),
        // STK Query response
        new Response(200, [], json_encode([
            'ResponseCode' => '0',
            'ResponseDescription' => 'The service request has been accepted successsfully',
            'MerchantRequestID' => 'test-merchant-request',
            'CheckoutRequestID' => 'test-checkout-request',
            'ResultCode' => '0',
            'ResultDesc' => 'The service request is processed successfully.'
        ]))
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $mpesa = new Mpesa($this->config);
    $mpesa->setClient($client);
    $mpesa->authenticate();

    $response = $mpesa->stkQuery('test-checkout-request');

    expect($response)->toBeArray()
        ->and($response)->toHaveKey('ResponseCode')
        ->and($response['ResponseCode'])->toBe('0');
});

test('mpesa can register urls', function () {
    $mock = new MockHandler([
        // Authentication response
        new Response(200, [], json_encode([
            'access_token' => 'test-token',
            'expires_in' => '3599'
        ])),
        // Register URLs response
        new Response(200, [], json_encode([
            'ConversationID' => 'test-conversation',
            'OriginatorCoversationID' => 'test-originator',
            'ResponseDescription' => 'Success'
        ]))
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $mpesa = new Mpesa($this->config);
    $mpesa->setClient($client);
    $mpesa->authenticate();

    $response = $mpesa->registerUrls('https://example.com/confirmation', 'https://example.com/validation');

    expect($response)->toBeArray()
        ->and($response)->toHaveKey('ConversationID')
        ->and($response)->toHaveKey('ResponseDescription')
        ->and($response['ResponseDescription'])->toBe('Success');
});

test('mpesa can initiate b2c payment', function () {
    $mock = new MockHandler([
        // Authentication response
        new Response(200, [], json_encode([
            'access_token' => 'test-token',
            'expires_in' => '3599'
        ])),
        // B2C response
        new Response(200, [], json_encode([
            'ConversationID' => 'test-conversation',
            'OriginatorConversationID' => 'test-originator',
            'ResponseCode' => '0',
            'ResponseDescription' => 'Accept the service request successfully.'
        ]))
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $mpesa = new Mpesa($this->config);
    $mpesa->setClient($client);

    $response = $mpesa->authenticate()
        ->setInitiatorName('test-initiator')
        ->setSecurityCredential('test-credential')
        ->setQueueTimeOutUrl('https://example.com/timeout')
        ->setResultUrl('https://example.com/result')
        ->setAmount(100.0)
        ->setPartyA('1020')
        ->setPartyB('251712345678')
        ->setRemarks('Test')
        ->setOccasion('Test Occasion')
        ->setCommandID('BusinessPayment')
        ->b2c();

    expect($response)->toBeInstanceOf(MpesaResponse::class)
        ->and($response->getResponseCode())->toBe(200)
        ->and($response->getResponseMessage())->toBe('Accept the service request successfully.');
});

test('mpesa handles api errors gracefully', function () {
    $mock = new MockHandler([
        new Response(400, [], json_encode([
            'requestId' => 'test-request',
            'errorCode' => 'Invalid Request',
            'errorMessage' => 'Invalid parameters'
        ]))
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $mpesa = new Mpesa($this->config);
    $mpesa->setClient($client);

    expect(fn() => $mpesa->stkPush(1.0, 'invalid-phone', 'TEST123', 'Test Payment'))->toThrow(\InvalidArgumentException::class);
});

test('mpesa can check account balance', function () {
    $mock = new MockHandler([
        // Authentication response
        new Response(200, [], json_encode([
            'access_token' => 'test-token',
            'expires_in' => '3599'
        ])),
        // Account Balance response
        new Response(200, [], json_encode([
            'Result' => [
                'ResultType' => 0,
                'ResultCode' => 0,
                'ResultDesc' => 'The service request is processed successfully.',
                'OriginatorConversationID' => 'test-conv-id',
                'ConversationID' => 'test-conv-id',
                'TransactionID' => 'test-trans-id',
                'ResultParameters' => [
                    'ResultParameter' => [
                        [
                            'Key' => 'AccountBalance',
                            'Value' => 'Working Account|ETB|1000.00&Utility Account|ETB|2000.00'
                        ]
                    ]
                ]
            ]
        ]))
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $mpesa = new Mpesa($this->config);
    $mpesa->setClient($client);

    $response = $mpesa->authenticate()
        ->setSecurityCredential('test-credential')
        ->setAccountBalanceInitiator('test-initiator')
        ->setAccountBalancePartyA('1020')
        ->setAccountBalanceRemarks('Test balance check')
        ->setAccountBalanceIdentifierType('4')
        ->setQueueTimeOutUrl('https://example.com/timeout')
        ->setResultUrl('https://example.com/result')
        ->checkAccountBalance();

    expect($response)->toBeArray()
        ->and($response)->toHaveKey('ResponseCode')
        ->and($response['ResponseCode'])->toBe('0');

    // Test balance parsing
    $balanceInfo = $mpesa->parseBalanceResult($response);
    expect($balanceInfo)->toBeArray()
        ->and($balanceInfo)->toHaveCount(2)
        ->and($balanceInfo[0])->toMatchArray([
                'account' => 'Working Account',
                'currency' => 'ETB',
                'amount' => '1000.00'
            ])
        ->and($balanceInfo[1])->toMatchArray([
                'account' => 'Utility Account',
                'currency' => 'ETB',
                'amount' => '2000.00'
            ]);
});

test('mpesa account balance validates required fields', function () {
    $mpesa = new Mpesa($this->config);
    $mpesa->setClient($this->client);
    $mpesa->authenticate();

    expect(fn() => $mpesa->checkAccountBalance())
        ->toThrow(\RuntimeException::class, 'Missing required fields for account balance');
});