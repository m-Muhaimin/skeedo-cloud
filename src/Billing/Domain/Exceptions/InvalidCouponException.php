<?php

declare(strict_types=1);

namespace Billing\Domain\Exceptions;

use Billing\Domain\Entities\CouponEntity;
use Exception;
use Throwable;

class InvalidCouponException extends Exception
{
    public function __construct(
        public readonly CouponEntity $coupon,
        public readonly CouponVoidType $type,
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            sprintf("Coupon <%s> is not valid!", $coupon->getCode()->value),
            $code,
            $previous
        );
    }
}
