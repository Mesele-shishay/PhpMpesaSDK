<?php

use MesaSDK\PhpMpesa\Config;

test('config can be instantiated with default values', function() {
    $config = new Config();
    
    expect($config->getBaseUrl())->toBe('https://apisandbox.safaricom.et')
        ->and($config->getConsumerKey())->toBe('')
        ->and($config->getConsumerSecret())->toBe('')
        ->and($config->getPasskey())->toBe('')
        ->and($config->getShortcode())->toBe('')
        ->and($config->getEnvironment())->toBe('sandbox');
});

test('config can be instantiated with custom values', function() {
    $config = new Config(
        'https://apisandbox.safaricom.et',
        'test_key',
        'test_secret',
        'test_passkey',
        'test_shortcode',
        'production'
    );
    
    expect($config->getBaseUrl())->toBe('https://apisandbox.safaricom.et')
        ->and($config->getConsumerKey())->toBe('test_key')
        ->and($config->getConsumerSecret())->toBe('test_secret')
        ->and($config->getPasskey())->toBe('test_passkey')
        ->and($config->getShortcode())->toBe('test_shortcode')
        ->and($config->getEnvironment())->toBe('production');
});

test('config setters return self for method chaining', function() {
    $config = new Config();
    
    expect($config->setBaseUrl('https://test.com'))->toBeInstanceOf(Config::class)
        ->and($config->setConsumerKey('key'))->toBeInstanceOf(Config::class)
        ->and($config->setConsumerSecret('secret'))->toBeInstanceOf(Config::class)
        ->and($config->setPasskey('passkey'))->toBeInstanceOf(Config::class)
        ->and($config->setShortcode('shortcode'))->toBeInstanceOf(Config::class)
        ->and($config->setEnvironment('sandbox'))->toBeInstanceOf(Config::class);
});

test('setEnvironment updates base URL automatically', function() {
    $config = new Config();
    
    $config->setEnvironment('production');
    expect($config->getBaseUrl())->toBe('https://apisandbox.safaricom.et');
    
    $config->setEnvironment('sandbox');
    expect($config->getBaseUrl())->toBe('https://apisandbox.safaricom.et');
});

test('setEnvironment throws exception for invalid environment', function() {
    $config = new Config();
    
    expect(fn() => $config->setEnvironment('invalid'))
        ->toThrow(InvalidArgumentException::class, "Environment must be either 'sandbox' or 'production'");
});

test('config values can be updated after instantiation', function() {
    $config = new Config();
    
    $config->setBaseUrl('https://test.com')
           ->setConsumerKey('new_key')
           ->setConsumerSecret('new_secret')
           ->setPasskey('new_passkey')
           ->setShortcode('new_shortcode');
    
    expect($config->getBaseUrl())->toBe('https://test.com')
        ->and($config->getConsumerKey())->toBe('new_key')
        ->and($config->getConsumerSecret())->toBe('new_secret')
        ->and($config->getPasskey())->toBe('new_passkey')
        ->and($config->getShortcode())->toBe('new_shortcode');
});

test('environment is case insensitive', function() {
    $config = new Config();
    
    $config->setEnvironment('PRODUCTION');
    expect($config->getEnvironment())->toBe('production')
        ->and($config->getBaseUrl())->toBe('https://apisandbox.safaricom.et');
    
    $config->setEnvironment('SaNdBoX');
    expect($config->getEnvironment())->toBe('sandbox')
        ->and($config->getBaseUrl())->toBe('https://apisandbox.safaricom.et');
}); 