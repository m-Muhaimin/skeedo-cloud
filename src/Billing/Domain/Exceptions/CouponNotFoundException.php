<?php

declare(strict_types=1);

namespace Billing\Domain\Exceptions;

use Billing\Domain\ValueObjects\Code;
use Exception;
use Shared\Domain\ValueObjects\Id;
use Throwable;

class CouponNotFoundException extends Exception
{
    public function __construct(
        public readonly Id|Code $key,
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            $key instanceof Id
                ? sprintf(
                    "Coupon with id <%s> doesn't exists!",
                    $key->getValue()
                )
                : sprintf(
                    "Coupon with code <%s> doesn't exists!",
                    $key->value
                ),
            $code,
            $previous
        );
    }
}
