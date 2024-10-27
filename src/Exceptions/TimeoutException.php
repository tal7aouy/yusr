<?php

declare(strict_types=1);

namespace Yusr\Http\Exceptions;

use Psr\Http\Message\RequestInterface;

class TimeoutException extends NetworkException
{
    public function __construct(RequestInterface $request, float $timeout, ?\Throwable $previous = null)
    {
        parent::__construct(
            sprintf('Request timed out after %.1f seconds', $timeout),
            $request,
            $previous
        );
    }
}
