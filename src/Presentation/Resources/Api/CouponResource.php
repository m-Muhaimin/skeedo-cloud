<?php

declare(strict_types=1);

namespace Presentation\Resources\Api;

use Billing\Domain\Entities\CouponEntity;
use JsonSerializable;
use Override;
use Presentation\Resources\Api\Traits\TwigResource;

class CouponResource implements JsonSerializable
{
    use TwigResource;
    public function __construct(
        private CouponEntity $coupon
    ) {}

    #[Override]
    public function jsonSerialize(): array
    {
        $coupon = $this->coupon;

        return [
            'id' => $coupon->getId(),
            'code' => $coupon->getCode(),
            'cycle_count' => $coupon->getCycleCount(),
            'discount_type' => $coupon->getDiscountType(),
            'amount' => $coupon->getAmount()
        ];
    }
}
