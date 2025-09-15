<?php

declare(strict_types=1);

namespace Affiliate\Application\Listeners;

use Affiliate\Application\Commands\TrackAffiliateCommand;
use Affiliate\Domain\Exceptions\AffiliateNotFoundException;
use Billing\Domain\Events\OrderFulfilledEvent;
use Billing\Infrastructure\Currency\ExchangeInterface;
use Easy\Container\Attributes\Inject;
use Shared\Infrastructure\CommandBus\Dispatcher;

class TrackConversion
{
    public function __construct(
        private Dispatcher $dispatcher,
        private ExchangeInterface $exchange,

        #[Inject('option.affiliates.is_enabled')]
        private bool $isEnabled = false,

        #[Inject('option.affiliates.commission')]
        private float $commission = 0,
    ) {}

    public function __invoke(OrderFulfilledEvent $event): void
    {
        $order = $event->order;
        $subtotal = $order->getTotalPrice();

        if ($subtotal->value <= 0) {
            return;
        }

        if (!$this->isEnabled) {
            return;
        }

        $user = $order->getWorkspace()->getOwner()->getReferredBy();

        if (!$user) {
            return;
        }

        $affiliate = $user->getAffiliate();

        if ($affiliate) {
            $commission = $this->commission / 100;
            $amount = $this->exchange->convert($subtotal, $order->getCurrencyCode(), 'USD');
            $amount = $amount->value * $commission;

            $cmd = new TrackAffiliateCommand($affiliate->getCode(), 'conversion');
            $cmd->setAmount((int) $amount);

            try {
                $this->dispatcher->dispatch($cmd);
            } catch (AffiliateNotFoundException) {
                // Do nothing if the affiliate is not found
            }
        }
    }
}
