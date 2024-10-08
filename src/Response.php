<?php

declare(strict_types=1);

namespace Yusr\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class Response implements ResponseInterface
{
    private int $statusCode;
    private string $reasonPhrase = '';
    private array $headers = [];
    private StreamInterface $body;
    private string $protocolVersion = '1.1';

    public function __construct(int $statusCode, array $headers = [], $body = '', string $version = '1.1', string $reason = '')
    {
        $this->statusCode = $statusCode;
        $this->headers = array_change_key_case($headers, CASE_LOWER);
        foreach ($this->headers as $name => $value) {
            $this->headers[$name] = (array) $value;
        }
        $this->body = $body instanceof StreamInterface ? $body : new Stream($body);
        $this->protocolVersion = $version;
        $this->reasonPhrase = $reason;
    }

    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    public function withProtocolVersion($version): self
    {
        $new = clone $this;
        $new->protocolVersion = $version;
        return $new;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function hasHeader($name): bool
    {
        return isset($this->headers[strtolower($name)]);
    }

    public function getHeader($name): array
    {
        $name = strtolower($name);
        if (! isset($this->headers[$name])) {
            return [];
        }
        return $this->headers[$name];
    }

    public function getHeaderLine($name): string
    {
        $name = strtolower($name);
        if (!isset($this->headers[$name])) {
            return '';
        }
        return implode(', ', $this->headers[$name]);
    }

    public function withHeader($name, $value): self
    {
        $new = clone $this;
        $new->headers[strtolower($name)] = (array) $value;
        return $new;
    }
    public function withAddedHeader($name, $value): self
    {
        $new = clone $this;
        $name = strtolower($name);
        if (!isset($new->headers[$name])) {
            $new->headers[$name] = [];
        }
        $new->headers[$name] = array_merge($new->headers[$name], (array) $value);
        return $new;
    }
    public function withoutHeader($name): self
    {
        $new = clone $this;
        unset($new->headers[strtolower($name)]);
        return $new;
    }

    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    public function withBody(StreamInterface $body): self
    {
        $new = clone $this;
        $new->body = $body;
        return $new;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function withStatus($code, $reasonPhrase = ''): self
    {
        $new = clone $this;
        $new->statusCode = $code;
        $new->reasonPhrase = $reasonPhrase;
        return $new;
    }

    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }
}
