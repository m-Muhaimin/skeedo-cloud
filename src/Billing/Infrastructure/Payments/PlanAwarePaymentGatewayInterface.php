<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Payments;

use Billing\Domain\Entities\PlanEntity;

interface PlanAwarePaymentGatewayInterface
{
    /**
     * Returns whether the payment gateway can be used for the given plan.
     *
     * @param PlanEntity $plan The plan to check.
     *
     * @return bool Whether the payment gateway can be used for the given plan.
     */
    public function supportsPlan(PlanEntity $plan): bool;
}
