<?php

declare(strict_types=1);

namespace Billing\Domain\Services;

use Billing\Domain\Entities\OrderEntity;
use Billing\Domain\Entities\SubscriptionEntity;
use Billing\Domain\Repositories\SubscriptionRepositoryInterface;
use Easy\Container\Attributes\Inject;
use Workspace\Domain\Entities\WorkspaceEntity;

class OrderFulfillmentService
{
    public function __construct(
        #[Inject('option.billing.trial_without_payment')]
        private ?bool $trialWithoutPayment = null,
    ) {
    }

    public function fulfill(OrderEntity $order): SubscriptionEntity|WorkspaceEntity
    {
        $plan = $order->getPlan();
        $ws = $order->getWorkspace();

        if ($plan->getBillingCycle()->isRenewable()) {
            $subs = SubscriptionEntity::createFromOrder($order);
            $ws->subscribe($subs);
            $order->fulfill();

            if (
                $this->trialWithoutPayment
                && $order->getTrialPeriodDays()->value > 0
            ) {
                $subs->cancel();
            }

            return $subs;
        }

        $ws->addCredits($plan->getCreditCount());
        $order->fulfill();

        return $ws;
    }
}
