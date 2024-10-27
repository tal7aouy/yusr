<?php

declare(strict_types=1);

namespace Yusr\Http\Retry;

interface RetryStrategy
{
    public function shouldRetry(int $attempt, \Throwable $exception): bool;
    public function getDelay(int $attempt): int;
}
