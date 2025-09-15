<?php

declare(strict_types=1);

namespace Affiliate\Domain\Exceptions;

use Affiliate\Domain\ValueObjects\Code;
use Exception;
use Throwable;

class AffiliateNotFoundException extends Exception
{
    public function __construct(
        public readonly Code $affiliateCode,
        int $code = 0,
        ?Throwable $previous = null
    ) {
        $message = sprintf(
            "Affiliate with code <%s> doesn't exists!",
            $affiliateCode->value
        );

        parent::__construct($message, $code, $previous);
    }
}
