<?php

use Yusr\Http\Response;
use Yusr\Http\Stream;

test('Response can be created with default values', function () {
    $response = new Response(200);

    expect($response->getStatusCode())->toBe(200)
        ->and($response->getReasonPhrase())->toBe('')
        ->and($response->getProtocolVersion())->toBe('1.1')
        ->and($response->getHeaders())->toBe([])
        ->and($response->getBody())->toBeInstanceOf(Stream::class);
});

test('Response can be created with custom values', function () {
    $body = new Stream('Test body');
    $response = new Response(201, ['Content-Type' => 'text/plain'], $body, '2.0', 'Created');

    expect($response->getStatusCode())->toBe(201)
        ->and($response->getReasonPhrase())->toBe('Created')
        ->and($response->getProtocolVersion())->toBe('2.0')
        ->and($response->getHeaders())->toBe(['content-type' => ['text/plain']])
        ->and($response->getBody())->toBe($body);
});

test('withProtocolVersion returns a new instance with updated version', function () {
    $response = new Response(200);
    $newResponse = $response->withProtocolVersion('2.0');

    expect($newResponse)->not->toBe($response)
        ->and($newResponse->getProtocolVersion())->toBe('2.0')
        ->and($response->getProtocolVersion())->toBe('1.1');
});

test('withHeader adds or replaces a header', function () {
    $response = new Response(200, ['Existing' => 'value']);
    $newResponse = $response->withHeader('New-Header', 'new value');

    expect($newResponse->getHeaders())->toBe([
        'existing' => ['value'],
        'new-header' => ['new value']
    ]);

    $newerResponse = $newResponse->withHeader('Existing', 'updated value');
    expect($newerResponse->getHeaders())->toBe([
        'existing' => ['updated value'],
        'new-header' => ['new value']
    ]);
});

test('withAddedHeader appends to existing header', function () {
    $response = new Response(200, ['Existing' => 'value1']);
    $newResponse = $response->withAddedHeader('Existing', 'value2');

    expect($newResponse->getHeaders())->toBe([
        'existing' => ['value1', 'value2']
    ]);
});

test('withoutHeader removes a header', function () {
    $response = new Response(200, ['Existing' => 'value', 'Remove' => 'me']);
    $newResponse = $response->withoutHeader('Remove');

    expect($newResponse->getHeaders())->toBe([
        'existing' => ['value']
    ])
        ->and($newResponse->hasHeader('Remove'))->toBeFalse();
});

test('withBody updates the body', function () {
    $response = new Response(200);
    $newBody = new Stream('New body');
    $newResponse = $response->withBody($newBody);

    expect($newResponse->getBody())->toBe($newBody)
        ->and($response->getBody())->not->toBe($newBody);
});

test('withStatus updates status code and reason phrase', function () {
    $response = new Response(200);
    $newResponse = $response->withStatus(404, 'Not Found');

    expect($newResponse->getStatusCode())->toBe(404)
        ->and($newResponse->getReasonPhrase())->toBe('Not Found')
        ->and($response->getStatusCode())->toBe(200);
});

test('getHeaderLine returns comma-separated header values', function () {
    $response = new Response(200, ['Accept' => ['application/json', 'text/html']]);

    expect($response->getHeaderLine('Accept'))->toBe('application/json, text/html')
        ->and($response->getHeaderLine('Non-Existent'))->toBe('');
});
