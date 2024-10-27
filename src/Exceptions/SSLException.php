<?php

declare(strict_types=1);

namespace Yusr\Http\Exceptions;

use Psr\Http\Message\RequestInterface;

class SSLException extends NetworkException
{
    public function __construct(RequestInterface $request, string $sslError, ?\Throwable $previous = null)
    {
        parent::__construct(
            sprintf('SSL Error: %s', $sslError),
            $request,
            $previous
        );
    }
}
