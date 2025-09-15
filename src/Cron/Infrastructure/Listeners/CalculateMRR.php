<?php

declare(strict_types=1);

namespace Cron\Infrastructure\Listeners;

use Cron\Domain\Events\CronEvent;
use Easy\Container\Attributes\Inject;
use Option\Application\Commands\SaveOptionCommand;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Shared\Infrastructure\CommandBus\Exception\NoHandlerFoundException;
use Throwable;
use Traversable;
use Workspace\Application\Commands\ListWorkspacesCommand;
use Workspace\Application\Commands\ReadWorkspaceCommand;
use Workspace\Domain\Entities\WorkspaceEntity;
use Billing\Domain\ValueObjects\BillingCycle;
use Billing\Domain\ValueObjects\SubscriptionStatus;
use Billing\Infrastructure\Currency\ExchangeInterface;
use DateTime;

class CalculateMRR
{
    public function __construct(
        private Dispatcher $dispatcher,
        private ExchangeInterface $exchange,

        #[Inject('option.billing.currency')]
        private string $currency = 'USD',

        #[Inject('option.mrr.calculated_at')]
        private ?string $calculatedAt = null,

        #[Inject('option.mrr.cursor_id')]
        private ?string $cursorId = null,

        #[Inject('option.mrr.cursor_value')]
        private ?int $cursorMrr = 0,

        #[Inject('option.mrr.value')]
        private ?int $value = null,
    ) {}

    /**
     * @throws NoHandlerFoundException
     */
    public function __invoke(CronEvent $event)
    {
        // If MRR was calculated less than a day ago 
        // and we're not in the middle of a calculation
        if (
            $this->calculatedAt
            && $this->calculatedAt + 86400 >= time()
            && !$this->cursorId
        ) {
            return;
        }

        // Start a new calculation or continue an existing one
        $mrr = $this->cursorId ? $this->cursorMrr : 0;
        $cursor = $this->getCursor();

        $cmd = new ListWorkspacesCommand();
        $cmd->hasSubscription = true;
        $cmd->setOrderBy('id', 'asc');
        $cmd->setLimit(250);

        if ($cursor) {
            $cmd->setCursor((string) $cursor->getId()->getValue());
        }

        /** @var Traversable<WorkspaceEntity> */
        $workspaces = $this->dispatcher->dispatch($cmd);

        $newCursor = null;
        foreach ($workspaces as $ws) {
            $newCursor = $ws;

            $sub = $ws->getSubscription();
            if (!$sub) {
                // This case is not expected, but it's good to check
                continue;
            }

            $order = $sub->getOrder();
            if (!$order) {
                // Subscription is not associated with an order, 
                // possibly created manually
                continue;
            }

            $plan = $sub->getPlan();
            $status = $sub->getStatus();

            // Skip subscriptions that are not active or canceled. 
            // Canceled subscriptions are not yet ended. 
            // Other statuses are not expected, except for trial, 
            // trialing does not count towards MRR
            if (!in_array($status, [
                SubscriptionStatus::ACTIVE,
                SubscriptionStatus::CANCELED,
            ])) {
                // This case is not expected, but it's good to check
                continue;
            }

            // Get the base price from the order
            $price = $order->getTotalPrice();
            $currency = $order->getCurrencyCode();
            $price = $this->exchange->convert($price, $currency, $this->currency);
            $price = (int) $price->value;

            // Handle different billing cycles
            $billingCycle = $plan->getBillingCycle();
            if (!in_array($billingCycle, [BillingCycle::MONTHLY, BillingCycle::YEARLY])) {
                continue; // Skip non-recurring billing cycles
            }

            // Convert yearly to monthly equivalent
            if ($billingCycle === BillingCycle::YEARLY) {
                $price = (int)($price / 12);
            }

            // Apply coupon discount if applicable
            $coupon = $order->getCoupon();
            if ($coupon) {
                $cycleCount = $coupon->getCycleCount()->value;

                // Check if the coupon is still applicable based on subscription age
                if ($cycleCount === null || $this->isWithinCouponCycles($sub, $cycleCount)) {
                    // Calculate the discounted price
                    $price = $coupon->calculateDiscountedAmount($price);
                }
            }

            // Add to MRR
            $mrr += $price;
        }

        $data = [
            'calculated_at' => time(),
            'value' => $mrr,
            'currency' => 'USD',
            'cursor_id' => null,
            'cursor_value' => null,
        ];

        if ($newCursor) {
            $data['cursor_id'] = (string) $newCursor->getId()->getValue();
            $data['cursor_value'] = $mrr;

            // Calculation is not complete yet, preserve current values
            $data['calculated_at'] = $this->calculatedAt;
            $data['value'] = $this->value;
        }

        $cmd = new SaveOptionCommand(
            'mrr',
            json_encode($data)
        );

        $this->dispatcher->dispatch($cmd);
    }

    /** @return null|WorkspaceEntity */
    private function getCursor(): ?WorkspaceEntity
    {
        if (!$this->cursorId) {
            return null;
        }

        try {
            $cmd = new ReadWorkspaceCommand($this->cursorId);

            /** @var WorkspaceEntity */
            $ws = $this->dispatcher->dispatch($cmd);
            return $ws;
        } catch (Throwable $th) {
            return null;
        }
    }

    /**
     * Determines if a subscription is still within the coupon's applicable cycles
     * 
     * @param SubscriptionEntity $subscription
     * @param int $cycleCount
     * @return bool
     */
    private function isWithinCouponCycles($subscription, int $cycleCount): bool
    {
        $createdAt = $subscription->getCreatedAt();
        $now = new DateTime();
        $billingCycle = $subscription->getPlan()->getBillingCycle();

        // Calculate how many cycles have passed
        $interval = $createdAt->diff($now);
        $monthsPassed = ($interval->y * 12) + $interval->m;

        if ($billingCycle === BillingCycle::YEARLY) {
            $cyclesPassed = $interval->y;
        } else {
            $cyclesPassed = $monthsPassed;
        }

        // Add one more cycle if we're more than 15 days into the current month/year
        if ($billingCycle === BillingCycle::YEARLY && $interval->m >= 6) {
            $cyclesPassed++;
        } elseif ($billingCycle === BillingCycle::MONTHLY && $interval->d >= 15) {
            $cyclesPassed++;
        }

        return $cyclesPassed < $cycleCount;
    }
}
