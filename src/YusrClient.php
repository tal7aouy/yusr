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
    private array $defaultOptions = [
        'timeout' => 30,
        'allow_redirects' => true,
        'http_errors' => true,
        'verify' => true,
        'headers' => [],
    ];
    private int $requestCount = 0;
    private int $rateLimit = 10; // Maximum requests allowed
    private int $rateLimitTimeFrame = 60; // Time frame in seconds
    private ?float $firstRequestTime = null;

    private function __construct(array $options = [])
    {
        $this->defaultOptions = array_merge($this->defaultOptions, $options);
    }

    public static function getInstance(array $options = []): YusrClient
    {
        if (! self::$instance instanceof \Yusr\Http\YusrClient) {
            self::$instance = new self($options);
        }
        return self::$instance;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->enforceRateLimit();

        $options = $this->prepareOptions();
        $curl = $this->createCurlHandleWrapper($request, $options);

        $responseBody = $this->curlExec($curl);
        $responseInfo = $this->curlGetInfo($curl);

        if ($responseBody === false) {
            $errorMessage = $this->curlError($curl);
            $errorCode = $this->curlErrno($curl);
            $this->curlClose($curl);
            throw new RequestException("cURL error $errorCode: $errorMessage", $request);
        }

        $this->curlClose($curl);

        $headers = $this->parseHeaders(substr($responseBody, 0, $responseInfo['header_size']));
        $body = substr($responseBody, $responseInfo['header_size']);

        return new Response(
            $responseInfo['http_code'],
            $headers,
            $body
        );
    }

    // Add these protected methods to make the class more testable
    protected function curlExec($curl)
    {
        return curl_exec($curl);
    }

    protected function curlGetInfo($curl, $opt = null)
    {
        return curl_getinfo($curl, $opt);
    }

    protected function curlError($curl)
    {
        return curl_error($curl);
    }

    protected function curlErrno($curl)
    {
        return curl_errno($curl);
    }

    protected function curlClose($curl)
    {
        curl_close($curl);
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

    private function prepareOptions(): array
    {
        return $this->defaultOptions;
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
    protected function createCurlHandleWrapper(RequestInterface $request, array $options): \CurlHandle
    {
        return $this->createCurlHandle($request, $options);
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

    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize singleton");
    }
    protected function curlSetopt($curl, $option, $value)
    {
        return curl_setopt($curl, $option, $value);
    }
    private function enforceRateLimit(): void
    {
        $currentTime = microtime(true);

        if ($this->firstRequestTime === null || ($currentTime - $this->firstRequestTime) > $this->rateLimitTimeFrame) {
            // Reset the count and time frame
            $this->requestCount = 1;
            $this->firstRequestTime = $currentTime;
        } else {
            if ($this->requestCount >= $this->rateLimit) {
                // Throw an exception if the rate limit is exceeded
                throw new \Exception("Rate limit exceeded");
            } else {
                $this->requestCount++;
            }
        }
    }
}
