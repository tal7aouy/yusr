<?php

declare(strict_types=1);

namespace Yusr\Http\Exceptions;

class RateLimitException extends \RuntimeException
{
    private int $limit;
    private int $timeFrame;

    public function __construct(int $limit, int $timeFrame, ?\Throwable $previous = null)
    {
        $this->limit = $limit;
        $this->timeFrame = $timeFrame;
        parent::__construct(
            sprintf('Rate limit of %d requests per %d seconds exceeded', $limit, $timeFrame),
            429,
            $previous
        );
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getTimeFrame(): int
    {
        return $this->timeFrame;
    }
}
