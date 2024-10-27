<?php

declare(strict_types=1);

namespace Yusr\Http\Exceptions;

use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestInterface;

class NetworkException extends RequestException implements NetworkExceptionInterface
{
    public function __construct(string $message, RequestInterface $request, ?\Throwable $previous = null)
    {
        parent::__construct($message, $request, $previous);
    }
}
