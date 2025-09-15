<?php

declare(strict_types=1);

namespace Presentation\Resources\Api;

use Billing\Domain\Entities\PlanEntity;
use JsonSerializable;
use Presentation\Resources\DateTimeResource;

class PlanResource implements JsonSerializable
{
    use Traits\TwigResource;

    public function __construct(
        private PlanEntity $plan
    ) {}

    public function jsonSerialize(): array
    {
        $plan = $this->plan;

        $output = [
            'id' => $plan->getId(),
            'title' => $plan->getTitle(),
            'description' => $plan->getDescription(),
            'icon' => $plan->getIcon(),
            'price' => $plan->getPrice(),
            'sale_price' => $plan->getSalePrice(),
            'billing_cycle' => $plan->getBillingCycle(),

            'credit_count' => is_null($plan->getCreditCount()->value)
                ? null : (int) $plan->getCreditCount()->value,

            'member_cap' => $plan->getMemberCap(),
            'created_at' => new DateTimeResource($plan->getCreatedAt()),
            'updated_at' => new DateTimeResource($plan->getUpdatedAt()),
            'is_featured' => $plan->getIsFeatured(),
            'superiority' => $plan->getSuperiority(),
            'snapshot' => new PlanSnapshotResource($plan->getSnapshot()),
            'config' => $plan->getConfig(),
            'coupon' => $plan->getCoupon()
                ? new CouponResource($plan->getCoupon()) : null,
            'discount' => $plan->getDiscount()
                ? $plan->getDiscount()->value : null,
        ];

        $list = $plan->getFeatureList()->value;
        array_walk(
            $list,
            fn(&$item) => $item = $item[0] == '-'
                ? [
                    'title' => trim(substr($item, 1)),
                    'is_included' => false
                ]
                : [
                    'title' => $item,
                    'is_included' => true
                ]
        );

        $output['feature_list'] = $list;

        return $output;
    }
}
