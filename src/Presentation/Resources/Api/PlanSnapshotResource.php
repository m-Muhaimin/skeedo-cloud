<?php

declare(strict_types=1);

namespace Presentation\Resources\Api;

use Billing\Domain\Entities\PlanSnapshotEntity;
use JsonSerializable;
use Presentation\Resources\DateTimeResource;

class PlanSnapshotResource implements JsonSerializable
{
    use Traits\TwigResource;

    public function __construct(
        private PlanSnapshotEntity $plan
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
            'billing_cycle' => $plan->getBillingCycle(),
            'credit_count' => $plan->getCreditCount(),
            'member_cap' => $plan->getMemberCap(),
            'created_at' => new DateTimeResource($plan->getCreatedAt()),
            'updated_at' => new DateTimeResource($plan->getUpdatedAt()),
            'config' => $plan->getConfig(),
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
