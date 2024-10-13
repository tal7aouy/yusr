<?php

declare(strict_types=1);

namespace Yusr\Http;

use Psr\Http\Message\StreamInterface;

class Stream implements StreamInterface
{
    private $stream;
    private $size;

    public function __construct(string $content = '')
    {
        $this->stream = fopen('php://temp', 'r+');
        fwrite($this->stream, $content);
        $this->size = strlen($content);
        rewind($this->stream);
    }

    public function __toString(): string
    {
        return $this->getContents();
    }

    public function close(): void
    {
        if (is_resource($this->stream)) {
            fclose($this->stream);
        }
        $this->detach();
    }

    public function detach()
    {
        if ($this->stream === null) {
            return null;
        }

        $result = $this->stream;
        unset($this->stream);
        $this->size = null;

        return $result;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function tell(): int
    {
        if ($this->stream === null) {
            throw new \RuntimeException('Stream is detached');
        }

        $result = ftell($this->stream);
        if ($result === false) {
            throw new \RuntimeException('Unable to determine stream position');
        }

        return $result;
    }

    public function eof(): bool
    {
        return $this->stream === null || feof($this->stream);
    }

    public function isSeekable(): bool
    {
        return $this->stream !== null && $this->getMetadata('seekable');
    }

    public function seek($offset, $whence = SEEK_SET): void
    {
        if ($this->stream === null) {
            throw new \RuntimeException('Stream is detached');
        }
        if (! $this->isSeekable()) {
            throw new \RuntimeException('Stream is not seekable');
        }
        if (fseek($this->stream, $offset, $whence) === -1) {
            throw new \RuntimeException('Unable to seek to stream position ' . $offset . ' with whence ' . var_export($whence, true));
        }
    }

    public function rewind(): void
    {
        $this->seek(0);
    }

    public function isWritable(): bool
    {
        return $this->stream !== null && is_writable($this->getMetadata('uri'));
    }

    public function write($string): int
    {
        if ($this->stream === null) {
            throw new \RuntimeException('Stream is detached');
        }
        if (! $this->isWritable()) {
            throw new \RuntimeException('Cannot write to a non-writable stream');
        }

        $result = fwrite($this->stream, $string);
        if ($result === false) {
            throw new \RuntimeException('Unable to write to stream');
        }

        $this->size = null;

        return $result;
    }

    public function isReadable(): bool
    {
        return $this->stream !== null && is_readable($this->getMetadata('uri'));
    }

    public function read($length): string
    {
        if ($this->stream === null) {
            throw new \RuntimeException('Stream is detached');
        }
        if (! $this->isReadable()) {
            throw new \RuntimeException('Cannot read from non-readable stream');
        }
        if ($length < 0) {
            throw new \RuntimeException('Length parameter cannot be negative');
        }

        $result = fread($this->stream, $length);
        if ($result === false) {
            throw new \RuntimeException('Unable to read from stream');
        }

        return $result;
    }

    public function getContents(): string
    {
        if ($this->stream === null) {
            throw new \RuntimeException('Stream is detached');
        }

        $contents = stream_get_contents($this->stream);
        if ($contents === false) {
            throw new \RuntimeException('Unable to read stream contents');
        }

        return $contents;
    }

    public function getMetadata($key = null)
    {
        if ($this->stream === null) {
            return $key ? null : [];
        }

        $meta = stream_get_meta_data($this->stream);

        if ($key === null) {
            return $meta;
        }

        return $meta[$key] ?? null;
    }
}
