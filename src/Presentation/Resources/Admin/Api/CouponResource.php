<?php

declare(strict_types=1);

namespace Presentation\Resources\Admin\Api;

use Billing\Domain\Entities\CouponEntity;
use JsonSerializable;
use Override;
use Presentation\Resources\Api\Traits\TwigResource;
use Presentation\Resources\DateTimeResource;

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
            'status' => $coupon->getStatus(),
            'title' => $coupon->getTitle(),
            'code' => $coupon->getCode(),
            'cycle_count' => $coupon->getCycleCount(),
            'redemption_limit' => $coupon->getRedemptionLimit(),
            'discount_type' => $coupon->getDiscountType(),
            'amount' => $coupon->getAmount(),
            'billing_cycle' => $coupon->getBillingCycle(),
            'created_at' => new DateTimeResource($coupon->getCreatedAt()),
            'updated_at' => new DateTimeResource($coupon->getUpdatedAt()),
            'starts_at' => new DateTimeResource($coupon->getStartsAt()),
            'expires_at' => new DateTimeResource($coupon->getExpiresAt()),
            'plan' => $coupon->getPlan() ? new PlanResource($coupon->getPlan()) : null,
            'redemption_count' => $coupon->getRedemptionCount(),
        ];
    }
}
