<?php

declare(strict_types=1);

namespace Presentation\Resources\Api;

use Billing\Domain\Entities\SubscriptionEntity;
use JsonSerializable;
use Presentation\Resources\CurrencyResource;
use Presentation\Resources\DateTimeResource;

class SubscriptionResource implements JsonSerializable
{
    use Traits\TwigResource;

    public function __construct(
        private SubscriptionEntity $subscription
    ) {}

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
            'renew_at' => new DateTimeResource($sub->getRenewAt()),
            'plan' => new PlanSnapshotResource($sub->getPlan()),
            'currency' => $sub->getOrder() ?  new CurrencyResource($sub->getOrder()->getCurrencyCode()) : null,
            'payment_gateway' => $sub->getPaymentGateway(),
            'external_id' => $sub->getExternalId(),
        ];

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
