<?php

use MesaSDK\PhpMpesa\Config;

test('config can be instantiated with valid parameters', function () {
    $config = new Config(
        'https://apisandbox.safaricom.et',
        'test_consumer_key',
        'test_consumer_secret',
        'test_passkey',
        '174379',
        'sandbox'
    );

    expect($config)->toBeInstanceOf(Config::class)
        ->and($config->getConsumerKey())->toBe('test_consumer_key')
        ->and($config->getConsumerSecret())->toBe('test_consumer_secret')
        ->and($config->getEnvironment())->toBe('sandbox')
        ->and($config->getShortcode())->toBe('174379');
});

test('config validates environment', function () {
    $config = new Config(
        'https://apisandbox.safaricom.et',
        'test_consumer_key',
        'test_consumer_secret',
        'test_passkey',
        '174379',
        'invalid'
    );
    expect(fn() => $config->validate())->toThrow(\InvalidArgumentException::class);
});

test('config requires consumer key', function () {
    $config = new Config(
        'https://apisandbox.safaricom.et',
        null,
        'test_consumer_secret',
        'test_passkey',
        '174379',
        'sandbox'
    );
    expect(fn() => $config->validate())->toThrow(\InvalidArgumentException::class);
});

test('config requires consumer secret', function () {
    $config = new Config(
        'https://apisandbox.safaricom.et',
        'test_consumer_key',
        null,
        'test_passkey',
        '174379',
        'sandbox'
    );
    expect(fn() => $config->validate())->toThrow(\InvalidArgumentException::class);
});

test('config can set and get security credential', function () {
    $config = new Config(
        'https://apisandbox.safaricom.et',
        'test_consumer_key',
        'test_consumer_secret',
        'test_passkey',
        '174379',
        'sandbox'
    );
    $newCredential = 'new_credential';

    $config->setPasskey($newCredential);

    expect($config->getPasskey())->toBe($newCredential);
});