<?php

declare(strict_types=1);

namespace Billing\Domain\ValueObjects;

use JsonSerializable;

/**
 * Represents the billing cycle for a plans
 */
enum BillingCycle: string implements JsonSerializable
{
    /**
     * Represents a one-time payment.
     * 
     * No usage reset. Credits can only be used after the current subscription's 
     * usage credits are exhausted. If there is no active subscription, credits 
     * are locked.
     */
    case ONE_TIME = 'one-time';

    /**
     * Represents a monthly recurring payment.
     * 
     * Payments are made every 30 days and usage is reset every 30 days.
     */
    case MONTHLY = 'monthly';

    /**
     * Represents a yearly recurring payment.
     * 
     * Payments are made every 365 days and usage is reset every 30 days.
     */
    case YEARLY = 'yearly';

    /**
     * Represents a one-time payment with a 30-day usage reset.
     */
    case LIFETIME = 'lifetime';

    /**
     * Returns the string representation of the billing cycle.
     *
     * @return string
     */
    public function jsonSerialize(): string
    {
        return $this->value;
    }

    /**
     * Checks if the billing cycle is recurring. 
     * 
     * Payments are recurring if the billing cycle is monthly or yearly.
     *
     * @return bool
     */
    public function isRecurring(): bool
    {
        return match ($this) {
            BillingCycle::MONTHLY, BillingCycle::YEARLY => true,
            default => false,
        };
    }

    public function isRenewable(): bool
    {
        return match ($this) {
            BillingCycle::MONTHLY, BillingCycle::YEARLY, BillingCycle::LIFETIME => true,
            default => false,
        };
    }
}
