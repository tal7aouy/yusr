<?php

declare(strict_types=1);

namespace Yusr\Http;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Yusr\Http\Exceptions\RateLimitException;
use Yusr\Http\Exceptions\RequestException;
use Yusr\Http\Retry\ExponentialBackoff;

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
        if (isset($options['rate_limit'])) {
            $this->rateLimit = $options['rate_limit'];
        }
        if (isset($options['rate_limit_timeframe'])) {
            $this->rateLimitTimeFrame = $options['rate_limit_timeframe'];
        }
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
        try {
            $this->enforceRateLimit();
        } catch (\Exception $e) {
            throw new RateLimitException($this->rateLimit, $this->rateLimitTimeFrame, $e);
        }

        $retryStrategy = new ExponentialBackoff();
        $attempt = 1;

        while (true) {
            try {
                $options = $this->prepareOptions();
                $curl = $this->createCurlHandleWrapper($request, $options);
                $responseBody = $this->curlExec($curl);
                $responseInfo = $this->curlGetInfo($curl);

                if ($responseBody === false) {
                    $errorCode = $this->curlErrno($curl);
                    $errorMessage = $this->curlError($curl);
                    $this->curlClose($curl);

                    $exception = $this->createExceptionFromCurlError(
                        $errorCode,
                        $errorMessage,
                        $request,
                        $options
                    );

                    if (! $retryStrategy->shouldRetry($attempt, $exception)) {
                        throw $exception;
                    }

                    usleep($retryStrategy->getDelay($attempt) * 1000);
                    $attempt++;
                    continue;
                }

                $this->curlClose($curl);

                $statusCode = $responseInfo['http_code'];
                if ($options['http_errors'] && $statusCode >= 400) {
                    $exception = new RequestException(
                        sprintf(
                            'HTTP request returned status code %d: %s',
                            $statusCode,
                            $this->getResponsePhrase($statusCode)
                        ),
                        $request
                    );

                    // Only retry on server errors (500+) or specific client errors
                    if ($statusCode >= 500 || in_array($statusCode, [408, 429])) {
                        if ($retryStrategy->shouldRetry($attempt, $exception)) {
                            usleep($retryStrategy->getDelay($attempt) * 1000);
                            $attempt++;
                            continue;
                        }
                    }

                    throw $exception;
                }

                $headers = $this->parseHeaders(substr($responseBody, 0, $responseInfo['header_size']));
                $body = substr($responseBody, $responseInfo['header_size']);

                return new Response(
                    $statusCode,
                    $headers,
                    $body
                );

            } catch (\Throwable $e) {
                // If it's not one of our exceptions, wrap it
                if (! ($e instanceof RequestException)) {
                    $e = new RequestException('Unexpected error: ' . $e->getMessage(), $request, $e);
                }

                if (! $retryStrategy->shouldRetry($attempt, $e)) {
                    throw $e;
                }

                usleep($retryStrategy->getDelay($attempt) * 1000);
                $attempt++;
            }
        }
    }

    private function createExceptionFromCurlError(
        int $errorCode,
        string $errorMessage,
        RequestInterface $request,
        array $options
    ): RequestException {
        switch ($errorCode) {
            case CURLE_OPERATION_TIMEOUTED:
                return new TimeoutException($request, $options['timeout']);

            case CURLE_COULDNT_CONNECT:
            case CURLE_COULDNT_RESOLVE_HOST:
            case CURLE_COULDNT_RESOLVE_PROXY:
                return new NetworkException(
                    "Connection failed: $errorMessage",
                    $request
                );

            case CURLE_SSL_CONNECT_ERROR:
            case CURLE_SSL_CERTPROBLEM:
            case CURLE_SSL_CIPHER:
            case CURLE_SSL_CACERT:
                return new SSLException($request, $errorMessage);

            default:
                return new RequestException(
                    "cURL error $errorCode: $errorMessage",
                    $request
                );
        }
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
            $line = trim($line);
            if (empty($line)) continue;
            
            $parts = explode(':', $line, 2);
            if (isset($parts[1])) {
                $name = trim($parts[0]);
                $value = trim($parts[1]);
                
                if (!isset($headers[$name])) {
                    $headers[$name] = [];
                }
                $headers[$name][] = $value;
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
    private function getResponsePhrase(int $statusCode): string
    {
        $phrases = [
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            408 => 'Request Timeout',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
        ];

        return $phrases[$statusCode] ?? 'Unknown Status Code';
    }
}
