<?php

declare(strict_types=1);

namespace Yusr\Http;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Yusr\Http\Exceptions\RequestException;

class YusrClient implements ClientInterface
{
    private static ?YusrClient $instance = null;
    private array $defaultOptions;

    private function __construct(array $options = [])
    {
        $this->defaultOptions = array_merge([
            'timeout' => 30,
            'allow_redirects' => true,
            'http_errors' => true,
            'verify' => true,
            'headers' => [],
        ], $options);
    }

    public static function getInstance(array $options = []): YusrClient
    {
        if (self::$instance === null) {
            self::$instance = new self($options);
        }
        return self::$instance;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $options = $this->prepareOptions($request);
        $curl = $this->createCurlHandle($request, $options);

        $responseBody = curl_exec($curl);
        $responseInfo = curl_getinfo($curl);

        if ($responseBody === false) {
            $errorMessage = curl_error($curl);
            $errorCode = curl_errno($curl);
            curl_close($curl);
            throw new RequestException("cURL error $errorCode: $errorMessage", $request);
        }

        curl_close($curl);

        $headers = $this->parseHeaders(substr($responseBody, 0, $responseInfo['header_size']));
        $body = substr($responseBody, $responseInfo['header_size']);

        return new Response(
            $responseInfo['http_code'],
            $headers,
            $body
        );
    }
    public function get(string $uri, array $options = []): ResponseInterface
    {
        return $this->request('GET', $uri, $options);
    }

    public function post(string $uri, array $options = []): ResponseInterface
    {
        return $this->request('POST', $uri, $options);
    }

    public function put(string $uri, array $options = []): ResponseInterface
    {
        return $this->request('PUT', $uri, $options);
    }

    public function delete(string $uri, array $options = []): ResponseInterface
    {
        return $this->request('DELETE', $uri, $options);
    }

    public function patch(string $uri, array $options = []): ResponseInterface
    {
        return $this->request('PATCH', $uri, $options);
    }

    public function request(string $method, string $uri, array $options = []): ResponseInterface
    {
        $request = new Request($method, $uri);

        if (isset($options['query'])) {
            $uri = $this->appendQueryString($uri, $options['query']);
            $request = $request->withUri(new Uri($uri));
        }

        if (isset($options['headers'])) {
            foreach ($options['headers'] as $name => $value) {
                $request = $request->withHeader($name, $value);
            }
        }

        if (isset($options['body'])) {
            $request = $request->withBody(new Stream($options['body']));
        }

        return $this->sendRequest($request);
    }

    private function prepareOptions(RequestInterface $request): array
    {
        $options = $this->defaultOptions;

        // Merge request-specific options here
        // For example, you might want to override the timeout for specific requests

        return $options;
    }


    private function createCurlHandle(RequestInterface $request, array $options): \CurlHandle
    {
        $curl = curl_init();

        $curlOptions = [
            CURLOPT_URL => (string) $request->getUri(),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_CUSTOMREQUEST => $request->getMethod(),
            CURLOPT_HTTPHEADER => $this->prepareHeaders($request),
            CURLOPT_TIMEOUT => $options['timeout'],
            CURLOPT_FOLLOWLOCATION => $options['allow_redirects'],
            CURLOPT_SSL_VERIFYPEER => $options['verify'],
        ];

        if ($request->getBody()->getSize() > 0) {
            $curlOptions[CURLOPT_POSTFIELDS] = (string) $request->getBody();
        }

        curl_setopt_array($curl, $curlOptions);

        return $curl;
    }
    private function prepareHeaders(RequestInterface $request): array
    {
        $headers = [];
        foreach ($request->getHeaders() as $name => $values) {
            $headers[] = $name . ': ' . implode(', ', $values);
        }
        return $headers;
    }

    private function parseHeaders(string $headerContent): array
    {
        $headers = [];
        $lines = explode("\n", $headerContent);
        foreach ($lines as $line) {
            $parts = explode(':', $line, 2);
            if (isset($parts[1])) {
                $headers[trim($parts[0])] = trim($parts[1]);
            }
        }
        return $headers;
    }

    private function appendQueryString(string $uri, array $query): string
    {
        $queryString = http_build_query($query);
        $separator = parse_url($uri, PHP_URL_QUERY) ? '&' : '?';
        return $uri . $separator . $queryString;
    }

    private function __clone() {}

    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize singleton");
    }
}
