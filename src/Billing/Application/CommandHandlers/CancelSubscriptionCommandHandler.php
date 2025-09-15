<?php

declare(strict_types=1);

namespace Billing\Application\CommandHandlers;

use Billing\Application\Commands\CancelSubscriptionCommand;
use Billing\Domain\Entities\SubscriptionEntity;
use Billing\Domain\Exceptions\SubscriptionNotFoundException;
use Billing\Domain\Repositories\SubscriptionRepositoryInterface;
use Billing\Infrastructure\Payments\Exceptions\GatewayNotFoundException;
use Billing\Infrastructure\Payments\Exceptions\PaymentException;
use Billing\Infrastructure\Payments\PaymentGatewayFactoryInterface;
use Shared\Domain\ValueObjects\Id;

class CancelSubscriptionCommandHandler
{
    public function __construct(
        private SubscriptionRepositoryInterface $repo,
        private PaymentGatewayFactoryInterface $factory,
    ) {
    }

    /**
     * @throws SubscriptionNotFoundException
     */
    public function handle(CancelSubscriptionCommand $cmd): SubscriptionEntity
    {
        $sub = $cmd->subscription instanceof Id
            ? $this->repo->ofId($cmd->subscription)
            : $cmd->subscription;

        $ws = $sub->getWorkspace();

        // Stop payments
        if ($sub->getPaymentGateway()->value) {
            try {
                $gateway = $this->factory->create(
                    $sub->getPaymentGateway()->value
                );

                $gateway->cancelSubscription($sub->getExternalId()->value);
            } catch (PaymentException | GatewayNotFoundException $th) {
                // Couldn't stop the payments, case must be handled manually
            }
        }

        $sub->cancel();

        $activeSub = $ws->getSubscription();

        if (
            !$activeSub
            || (string) $activeSub->getId()->getValue() !== (string) $sub->getId()->getValue()
        ) {
            // This is not the active subscription, so we can end it here
            $sub->end();
        }

        return $sub;
    }
}
