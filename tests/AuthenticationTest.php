<?php

use MesaSDK\PhpMpesa\Authentication;
use MesaSDK\PhpMpesa\Config;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;

// Mock successful authentication response
beforeEach(function() {
    $this->config = new Config([
        'consumer_key' => 'test_key',
        'consumer_secret' => 'test_secret',
        'environment' => 'sandbox'
    ]);
});

test('authentication can be instantiated with config', function() {
    $auth = new Authentication($this->config);
    expect($auth)->toBeInstanceOf(Authentication::class);
});

test('authentication successfully gets access token', function() {
    // Create a mock response
    $mock = new MockHandler([
        new Response(200, [], json_encode(['access_token' => 'test_token']))
    ]);
    
    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);
    
    $auth = new Authentication($this->config);
    $result = $auth->authenticate();
    
    expect($result)->toBeInstanceOf(Authentication::class)
        ->and($auth->hasToken())->toBeTrue()
        ->and($auth->getToken())->toBe('test_token');
});

test('authentication throws exception on invalid response', function() {
    // Create a mock response with invalid data
    $mock = new MockHandler([
        new Response(200, [], json_encode(['invalid_key' => 'value']))
    ]);
    
    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);
    
    $auth = new Authentication($this->config);
    
    expect(fn() => $auth->authenticate())
        ->toThrow(RuntimeException::class, 'Failed to get access token from response');
});

test('getToken throws exception when not authenticated', function() {
    $auth = new Authentication($this->config);
    
    expect(fn() => $auth->getToken())
        ->toThrow(RuntimeException::class, 'No access token available. Call authenticate() first.');
});

test('hasToken returns false when not authenticated', function() {
    $auth = new Authentication($this->config);
    expect($auth->hasToken())->toBeFalse();
});

test('authentication handles network errors gracefully', function() {
    // Create a mock that simulates a network error
    $mock = new MockHandler([
        new RequestException('Network error', new \GuzzleHttp\Psr7\Request('GET', 'test'))
    ]);
    
    $handlerStack = HandlerStack::create($mock);
    $client = new Client(['handler' => $handlerStack]);
    
    $auth = new Authentication($this->config);
    
    expect(fn() => $auth->authenticate())
        ->toThrow(RuntimeException::class, 'Authentication failed: Network error');
}); 