<?php

use Yusr\Http\Request;
use Yusr\Http\Stream;
use Yusr\Http\Uri;

test('Request can be created with minimal parameters', function (): void {
    $request = new Request('GET', 'https://example.com');

    expect($request->getMethod())->toBe('GET');
    expect($request->getUri()->__toString())->toBe('https://example.com');
    expect($request->getProtocolVersion())->toBe('1.1');
});

test('Request with custom headers and body', function (): void {
    $body = new Stream('test body');
    $request = new Request(
        'POST',
        'https://api.example.com/users',
        ['Content-Type' => 'application/json'],
        $body,
        '2.0'
    );

    expect($request->getMethod())->toBe('POST');
    expect($request->getUri()->__toString())->toBe('https://api.example.com/users');
    expect($request->getProtocolVersion())->toBe('2.0');
    expect($request->getHeaders())->toHaveKey('content-type');
    expect($request->getHeaderLine('content-type'))->toBe('application/json');
    expect($request->getBody())->toBe($body);
});

test('withRequestTarget modifies the request target', function (): void {
    $request = new Request('GET', 'https://example.com');
    $newRequest = $request->withRequestTarget('/custom-target');

    expect($newRequest->getRequestTarget())->toBe('/custom-target');
    expect($request->getRequestTarget())->not->toBe('/custom-target');
});

test('withMethod changes the HTTP method', function (): void {
    $request = new Request('GET', 'https://example.com');
    $newRequest = $request->withMethod('POST');

    expect($newRequest->getMethod())->toBe('POST');
    expect($request->getMethod())->toBe('GET');
});

test('withUri updates the URI and optionally the host header', function (): void {
    $request = new Request('GET', 'https://example.com');
    $newUri = new Uri('https://api.example.com');

    $newRequest = $request->withUri($newUri);
    expect($newRequest->getUri()->__toString())->toBe('https://api.example.com');
    expect($newRequest->getHeaderLine('Host'))->toBe('api.example.com');

    $preserveHostRequest = $request->withUri($newUri, true);
    expect($preserveHostRequest->getHeaderLine('Host'))->toBe('example.com');
});

test('header methods work correctly', function (): void {
    $request = new Request('GET', 'https://example.com', ['X-Test' => 'value']);

    expect($request->hasHeader('X-Test'))->toBeTrue();
    expect($request->getHeader('X-Test'))->toBe(['value']);
    expect($request->getHeaderLine('X-Test'))->toBe('value');

    $newRequest = $request->withHeader('X-New', 'new-value');
    expect($newRequest->hasHeader('X-New'))->toBeTrue();
    expect($newRequest->getHeaderLine('X-New'))->toBe('new-value');

    $addedRequest = $newRequest->withAddedHeader('X-New', 'another-value');
    expect($addedRequest->getHeader('X-New'))->toBe(['new-value', 'another-value']);

    $removedRequest = $addedRequest->withoutHeader('X-New');
    expect($removedRequest->hasHeader('X-New'))->toBeFalse();
});

test('withBody replaces the body stream', function (): void {
    $request = new Request('GET', 'https://example.com');
    $newBody = new Stream('new body content');

    $newRequest = $request->withBody($newBody);

    expect($newRequest->getBody())->toBe($newBody);
    expect($request->getBody())->not->toBe($newBody);
});

test('getHeaders returns all headers', function (): void {
    $headers = [
        'Content-Type' => 'application/json',
        'X-Custom' => ['value1', 'value2'],
    ];
    $request = new Request('GET', 'https://example.com', $headers);

    expect($request->getHeaders())->toBe([
        'content-type' => ['application/json'],
        'x-custom' => ['value1', 'value2'],
        'host' => ['example.com'],
    ]);
});
