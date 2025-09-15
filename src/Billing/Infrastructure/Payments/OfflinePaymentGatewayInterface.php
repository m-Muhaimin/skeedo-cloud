<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Payments;

/**
 * This interface is used to represent an offline payment gateway such as
 * bank transfer or manual payment.
 */
interface OfflinePaymentGatewayInterface extends PaymentGatewayInterface
{
    /**
     * Returns the icon of the payment gateway.
     *
     * @return string|null The icon of the payment gateway.
     */
    public function getIcon(): ?string;
}
