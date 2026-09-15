<?php

declare(strict_types=1);

namespace Aazsamir\Libphpsky\Client;

class AuthFactorTokenRequiredException extends AuthException
{
    public static function fromAuthException(AuthException $e): self
    {
        return new self(
            error: $e->getError(),
            message: $e->getMessage(),
            code: $e->getCode(),
            previous: $e,
            response: $e->getResponse(),
            host: $e->getHost(),
            endpoint: $e->getEndpoint(),
            query: $e->getQuery(),
        );
    }
}
