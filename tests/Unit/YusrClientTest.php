<?php

use Yusr\Http\Uri;
use Yusr\Http\Response;
use Yusr\Http\YusrClient;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

beforeEach(function (): void {
    $this->client = YusrClient::getInstance();
});

test('getInstance returns a singleton instance', function (): void {
    $instance1 = YusrClient::getInstance();
    $instance2 = YusrClient::getInstance();
    expect($instance1)->toBe($instance2);
});

test('HTTP methods call request method with correct parameters', function ($method): void {
    $mockClient = Mockery::mock(YusrClient::class)->makePartial();
    $mockClient->shouldAllowMockingProtectedMethods();
    $mockClient->shouldReceive('request')
        ->once()
        ->with(strtoupper($method), 'https://api.example.com', ['option' => 'value'])
        ->andReturn(Mockery::mock(ResponseInterface::class));

    $response = $mockClient->$method('https://api.example.com', ['option' => 'value']);
    expect($response)->toBeInstanceOf(ResponseInterface::class);
})->with(['get', 'post', 'put', 'delete', 'patch']);

test('request method creates correct request and calls sendRequest', function (): void {
    $mockClient = Mockery::mock(YusrClient::class)->makePartial();
    $mockClient->shouldAllowMockingProtectedMethods();
    $mockClient->shouldReceive('sendRequest')
        ->once()
        ->andReturn(Mockery::mock(ResponseInterface::class));

    $response = $mockClient->request('GET', 'https://api.example.com', ['query' => ['param' => 'value']]);
    expect($response)->toBeInstanceOf(ResponseInterface::class);
});
test('sendRequest handles successful requests', function (): void {
    $mockClient = Mockery::mock(YusrClient::class)->makePartial();
    $mockClient->shouldAllowMockingProtectedMethods();

    $jsonPlaceholderUrl = 'https://jsonplaceholder.typicode.com/todos/1';

    $mockRequest = Mockery::mock(RequestInterface::class);
    $mockRequest->shouldReceive('getUri')->andReturn($jsonPlaceholderUrl);
    $mockRequest->shouldReceive('getMethod')->andReturn('GET');
    $mockRequest->shouldReceive('getHeaders')->andReturn([]);
    $mockRequest->shouldReceive('getBody->getSize')->andReturn(0);

    // Mock createCurlHandleWrapper to return a mock curl resource
    $mockCurl = curl_init();
    $mockClient->shouldReceive('createCurlHandleWrapper')->andReturn($mockCurl);

    // Mock curlExec to return a successful response from JSONPlaceholder
    $mockResponse = json_encode([
        'userId' => 1,
        'id' => 1,
        'title' => 'delectus aut autem',
        'completed' => false
    ]);
    $mockClient->shouldReceive('curlExec')->with($mockCurl)->andReturn($mockResponse);

    // Mock curlGetInfo to return response info
    $mockClient->shouldReceive('curlGetInfo')->with($mockCurl)->andReturn([
        'http_code' => 200,
        'header_size' => 0, // We're not including headers in our mock response
    ]);

    // Mock curlClose
    $mockClient->shouldReceive('curlClose')->with($mockCurl)->once();

    $response = $mockClient->sendRequest($mockRequest);

    expect($response)->toBeInstanceOf(ResponseInterface::class);
    expect($response->getStatusCode())->toBe(200);
    expect(json_decode((string)$response->getBody(), true))->toBe([
        'userId' => 1,
        'id' => 1,
        'title' => 'delectus aut autem',
        'completed' => false
    ]);
});
test('appendQueryString correctly appends query parameters to URI', function (): void {
    $client = YusrClient::getInstance();
    $reflectionClass = new ReflectionClass(YusrClient::class);
    $method = $reflectionClass->getMethod('appendQueryString');
    $method->setAccessible(true);

    $uri = 'https://api.example.com';
    $query = ['param1' => 'value1', 'param2' => 'value2'];

    $result = $method->invoke($client, $uri, $query);

    expect($result)->toBe('https://api.example.com?param1=value1&param2=value2');
});

test('sendRequest enforces rate limit', function (): void {
    $client = YusrClient::getInstance();
    $reflectionClass = new ReflectionClass(YusrClient::class);

    $rateLimitProperty = $reflectionClass->getProperty('rateLimit');
    $rateLimitProperty->setAccessible(true);
    $rateLimit = $rateLimitProperty->getValue($client);

    // Create a mock request
    $mockRequest = Mockery::mock(RequestInterface::class);

    // Create a valid URI instance
    $mockUri = new Uri('https://jsonplaceholder.typicode.com/todos');

    // Set up the mock request to return the valid URI
    $mockRequest->shouldReceive('getUri')->andReturn($mockUri);
    $mockRequest->shouldReceive('getMethod')->andReturn('GET');
    $mockRequest->shouldReceive('getHeaders')->andReturn([]);
    $mockRequest->shouldReceive('getBody->getSize')->andReturn(0);

    // Simulate the maximum allowed requests
    for ($i = 0; $i < $rateLimit; $i++) {
        $client->sendRequest($mockRequest);
    }
    // Now, the next call to sendRequest should trigger the rate limit enforcement
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Rate limit exceeded'); // Adjust this message based on your implementation

    // This should wait until the rate limit resets
    $client->sendRequest($mockRequest);
});

afterEach(function (): void {
    Mockery::close();
});
