<?php

use MesaSDK\PhpMpesa\Mpesa;
use MesaSDK\PhpMpesa\Config;
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

    expect($response)->toBeInstanceOf(Mpesa::class);
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
        ->and($response)->toHaveKey('ResultCode')
        ->and($response['ResultCode'])->toBe('0');
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
    $mpesa->authenticate();

    $response = $mpesa->b2c(
        'test-initiator',
        'test-credential',
        'BusinessPayment',
        100.0,
        '174379',
        '251712345678',
        'Test payment',
        'Test'
    );

    expect($response)->toBeInstanceOf(\MesaSDK\PhpMpesa\Responses\MpesaResponse::class)
        ->and($response->getData())->toHaveKey('ConversationID')
        ->and($response->getData()['ResponseCode'])->toBe('0');
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