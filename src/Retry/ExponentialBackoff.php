<?php

declare(strict_types=1);

namespace Yusr\Http\Retry;

class ExponentialBackoff implements RetryStrategy
{
    private int $maxAttempts;
    private int $baseDelay;
    
    public function __construct(int $maxAttempts = 3, int $baseDelay = 1000)
    {
        $this->maxAttempts = $maxAttempts;
        $this->baseDelay = $baseDelay;
    }

    public function shouldRetry(int $attempt, \Throwable $exception): bool
    {
        return $attempt < $this->maxAttempts;
    }

    public function getDelay(int $attempt): int
    {
        return $this->baseDelay * (2 ** ($attempt - 1));
    }
}
