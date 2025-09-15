<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Payments\Gateways\BankTransfer;

use Billing\Domain\Entities\OrderEntity;
use Billing\Domain\Entities\PlanEntity;
use Billing\Domain\ValueObjects\BillingCycle;
use Billing\Domain\ValueObjects\ExternalId;
use Billing\Domain\ValueObjects\PaymentGateway;
use Billing\Infrastructure\Payments\OfflinePaymentGatewayInterface;
use Billing\Infrastructure\Payments\PlanAwarePaymentGatewayInterface;
use Billing\Infrastructure\Payments\PurchaseToken;
use Billing\Infrastructure\Payments\WebhookHandlerInterface;
use Easy\Container\Attributes\Inject;
use Override;
use Psr\Http\Message\UriInterface;
use Ramsey\Uuid\Uuid;
use Shared\Infrastructure\Atributes\BuiltInAspect;

#[BuiltInAspect]
class BankTransfer implements
    OfflinePaymentGatewayInterface,
    PlanAwarePaymentGatewayInterface
{
    public const LOOKUP_KEY = 'bank-transfer';

    public function __construct(
        #[Inject('option.bank_transfer.is_enabled')]
        private bool $isEnabled = false,

        #[Inject('option.bank_transfer.enabled_for')]
        private string $enabledFor = 'one-time'
    ) {}

    #[Override]
    public function getIcon(): ?string
    {
        return 'ti ti-building-bank';
    }

    #[Override]
    public function supportsPlan(PlanEntity $plan): bool
    {
        $cycle = $plan->getBillingCycle();

        if ($this->enabledFor === 'one-time') {
            return !$cycle->isRecurring();
        }

        if ($this->enabledFor === 'annual') {
            return !$cycle->isRecurring() || $cycle === BillingCycle::YEARLY;
        }

        return true;
    }

    #[Override]
    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    #[Override]
    public function getName(): string
    {
        return 'Bank Transfer';
    }

    #[Override]
    public function purchase(
        OrderEntity $order
    ): UriInterface|PurchaseToken|string {
        $token = Uuid::uuid4()->toString();
        $order->initiatePayment(
            new PaymentGateway(self::LOOKUP_KEY),
            new ExternalId($token)
        );

        return new PurchaseToken($token);
    }

    #[Override]
    public function completePurchase(
        OrderEntity $order,
        array $params = []
    ): string {
        return $order->getExternalId()->value ?: '';
    }

    #[Override]
    public function cancelSubscription(string $id): void
    {
        // Do nothing as this is a manual payment method
    }

    #[Override]
    public function getWebhookHandler(): string|WebhookHandlerInterface
    {
        return WebhookRequestHandler::class;
    }
}
