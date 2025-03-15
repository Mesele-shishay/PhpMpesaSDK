<?php

use MesaSDK\PhpMpesa\Authentication;
use MesaSDK\PhpMpesa\Config;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use MesaSDK\PhpMpesa\Exceptions\MpesaException;

beforeEach(function () {
    $this->config = new Config(
        'https://apisandbox.safaricom.et',
        'test_consumer_key',
        'test_consumer_secret',
        'test_passkey',
        '174379',
        'sandbox'
    );
});

test('authentication can generate basic auth', function () {
    $auth = new Authentication($this->config);
    $basicAuth = $auth->generateBasicAuth();

    expect($basicAuth)->toBeString()
        ->and($basicAuth)->toStartWith('Basic ');
});

test('authentication can get access token', function () {
    // Mock successful API response
    $mock = new MockHandler([
        new Response(200, [], json_encode([
            'access_token' => 'test_access_token',
            'expires_in' => '3599'
        ]))
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $auth = new Authentication($this->config);
    $auth->setClient($client);
    $token = $auth->authenticate();

    expect($token)->toBeString()
        ->and($token)->toBe('test_access_token');
});

test('authentication handles api errors', function () {
    // Mock error response
    $mock = new MockHandler([
        new Response(401, [], json_encode([
            'errorCode' => '999991',
            'errorMessage' => 'Invalid client id'
        ]))
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $auth = new Authentication($this->config);
    $auth->setClient($client);

    expect(fn() => $auth->authenticate())->toThrow(MpesaException::class);
});

test('authentication caches token', function () {
    // Mock successful API response
    $mock = new MockHandler([
        new Response(200, [], json_encode([
            'access_token' => 'test_access_token',
            'expires_in' => '3599'
        ]))
    ]);

    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);

    $auth = new Authentication($this->config);
    $auth->setClient($client);

    // First call should make an API request
    $token1 = $auth->authenticate();

    // Second call should return cached token
    $token2 = $auth->authenticate();

    expect($token1)->toBe($token2)
        ->and($token1)->toBe('test_access_token');
});