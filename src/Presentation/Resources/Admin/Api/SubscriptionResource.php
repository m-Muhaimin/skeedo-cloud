<?php

declare(strict_types=1);

namespace Presentation\Resources\Admin\Api;

use Billing\Domain\Entities\SubscriptionEntity;
use JsonSerializable;
use Presentation\Resources\DateTimeResource;

class SubscriptionResource implements JsonSerializable
{
    public function __construct(
        private SubscriptionEntity $subscription,
        private array $extend = []
    ) {
    }

    public function jsonSerialize(): array
    {
        $sub = $this->subscription;

        $output = [
            'id' => $sub->getId(),
            'trial_period_days' => $sub->getTrialPeriodDays(),
            'usage_count' => $sub->getUsageCount(),
            'usage_percentage' => $this->usagePercentage(),
            'credit_count' => $sub->getCredit(),
            'credit_percentage' => $this->creditPercentage(),
            'created_at' => new DateTimeResource($sub->getCreatedAt()),
            'updated_at' => new DateTimeResource($sub->getUpdatedAt()),
            'canceled_at' => new DateTimeResource($sub->getCanceledAt()),
            'cancel_at' => new DateTimeResource($sub->getCancelAt()),
            'ended_at' => new DateTimeResource($sub->getEndedAt()),
            'renew_at' => new DateTimeResource($sub->getRenewAt()),
            'plan' => new PlanSnapshotResource($sub->getPlan()),
            'status' => $sub->getStatus(),
            'payment_gateway' => $sub->getPaymentGateway(),
            'external_id' => $sub->getExternalId(),
        ];

        if (in_array('workspace', $this->extend)) {
            // Workspace extends (Seatch $this->extend for values start with "workspace." and remove "workspace." from the value)
            $extend = array_map(
                fn ($v) => str_replace('workspace.', '', $v),

                array_filter(
                    $this->extend,
                    fn ($v) => str_starts_with($v, 'workspace.')
                )
            );


            $output['workspace'] = new WorkspaceResource(
                $sub->getWorkspace(),
                $extend
            );
        }

        if (in_array('order', $this->extend)) {
            $output['order'] = $sub->getOrder() ? new OrderResource($sub->getOrder()) : null;
        }

        return $output;
    }

    private function creditPercentage(): string
    {
        $sub = $this->subscription;
        $credit = $sub->getCredit()->value;
        $planCredit = $sub->getPlan()->getCreditCount()->value;

        if ($planCredit === null) {
            return '100%';
        }

        if ($planCredit == 0) {
            return '0%';
        }

        return number_format($credit / $planCredit * 100, 2) . '%';
    }

    private function usagePercentage(): string
    {
        $sub = $this->subscription;
        $usage = $sub->getUsageCount()->value;
        $planCredit = $sub->getPlan()->getCreditCount()->value;

        if ($planCredit === null) {
            return '0%';
        }

        if ($planCredit == 0) {
            return '100%';
        }

        return number_format($usage / $planCredit * 100, 2) . '%';
    }
}
