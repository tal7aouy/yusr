<?php

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Yusr\Http\Response;
use Yusr\Http\YusrClient;

beforeEach(function () {
    $this->client = YusrClient::getInstance();
});

test('getInstance returns a singleton instance', function () {
    $instance1 = YusrClient::getInstance();
    $instance2 = YusrClient::getInstance();
    expect($instance1)->toBe($instance2);
});

test('HTTP methods call request method with correct parameters', function ($method) {
    $mockClient = Mockery::mock(YusrClient::class)->makePartial();
    $mockClient->shouldAllowMockingProtectedMethods();
    $mockClient->shouldReceive('request')
        ->once()
        ->with(strtoupper($method), 'https://api.example.com', ['option' => 'value'])
        ->andReturn(Mockery::mock(ResponseInterface::class));

    $response = $mockClient->$method('https://api.example.com', ['option' => 'value']);
    expect($response)->toBeInstanceOf(ResponseInterface::class);
})->with(['get', 'post', 'put', 'delete', 'patch']);

test('request method creates correct request and calls sendRequest', function () {
    $mockClient = Mockery::mock(YusrClient::class)->makePartial();
    $mockClient->shouldAllowMockingProtectedMethods();
    $mockClient->shouldReceive('sendRequest')
        ->once()
        ->andReturn(Mockery::mock(ResponseInterface::class));

    $response = $mockClient->request('GET', 'https://api.example.com', ['query' => ['param' => 'value']]);
    expect($response)->toBeInstanceOf(ResponseInterface::class);
});

test('sendRequest handles successful requests', function () {
    $mockClient = Mockery::mock(YusrClient::class)->makePartial();
    $mockClient->shouldAllowMockingProtectedMethods();

    // Initialize the defaultOptions property
    $reflectionProperty = new ReflectionProperty(YusrClient::class, 'defaultOptions');
    $reflectionProperty->setAccessible(true);
    $reflectionProperty->setValue($mockClient, [
        'timeout' => 30,
        'allow_redirects' => true,
        'http_errors' => true,
        'verify' => true,
        'headers' => [],
    ]);

    $mockUri = Mockery::mock(UriInterface::class);
    $mockUri->shouldReceive('__toString')->andReturn('https://api.example.com');

    $mockRequest = Mockery::mock(RequestInterface::class);
    $mockRequest->shouldReceive('getUri')->andReturn($mockUri);
    $mockRequest->shouldReceive('getMethod')->andReturn('GET');
    $mockRequest->shouldReceive('getHeaders')->andReturn([]);
    $mockRequest->shouldReceive('getBody->getSize')->andReturn(0);

    // Mock createCurlHandleWrapper to return a mock curl resource
    $mockClient->shouldReceive('createCurlHandleWrapper')->andReturn(curl_init());

    // Mock curl_exec to return a successful response
    $mockResponse = "HTTP/1.1 200 OK\r\nContent-Type: application/json\r\n\r\n{\"status\":\"success\"}";
    $mockClient->shouldReceive('curlExec')->andReturn($mockResponse);

    // Mock curl_getinfo to return response info
    $mockClient->shouldReceive('curlGetInfo')->andReturn([
        'http_code' => 200,
        'header_size' => strlen("HTTP/1.1 200 OK\r\nContent-Type: application/json\r\n\r\n"),
    ]);

    // Mock curl_error and curl_errno to return no error
    $mockClient->shouldReceive('curlError')->andReturn('');
    $mockClient->shouldReceive('curlErrno')->andReturn(0);

    // Mock curl_close
    $mockClient->shouldReceive('curlClose');

    $response = $mockClient->sendRequest($mockRequest);

    expect($response)->toBeInstanceOf(ResponseInterface::class);
    expect($response->getStatusCode())->toBe(200);
    expect($response->getHeaderLine('Content-Type'))->toBe('application/json');
    expect((string)$response->getBody())->toBe('{"status":"success"}');
});

test('appendQueryString correctly appends query parameters to URI', function () {
    $client = YusrClient::getInstance();
    $reflectionClass = new ReflectionClass(YusrClient::class);
    $method = $reflectionClass->getMethod('appendQueryString');
    $method->setAccessible(true);

    $uri = 'https://api.example.com';
    $query = ['param1' => 'value1', 'param2' => 'value2'];

    $result = $method->invoke($client, $uri, $query);

    expect($result)->toBe('https://api.example.com?param1=value1&param2=value2');
});
afterEach(function () {
    Mockery::close();
});
