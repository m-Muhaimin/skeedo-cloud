<?php

declare(strict_types=1);

namespace Billing\Domain\ValueObjects;

use JsonSerializable;

/**
 * Represents the discount type for a coupon
 */
enum DiscountType: string implements JsonSerializable
{
    /**
     * Represents a percentage discount.
     */
    case PERCENTAGE = 'percentage';

    /**
     * Represents a fixed discount.
     */
    case FIXED = 'fixed';

    /**
     * Returns the string representation of the discount type.
     *
     * @return string
     */
    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
