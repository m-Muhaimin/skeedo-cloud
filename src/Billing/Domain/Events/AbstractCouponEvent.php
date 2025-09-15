<?php

declare(strict_types=1);

namespace Billing\Domain\Events;

use Billing\Domain\Entities\CouponEntity;

abstract class AbstractCouponEvent
{
    public function __construct(
        public readonly CouponEntity $coupon,
    ) {}
}
