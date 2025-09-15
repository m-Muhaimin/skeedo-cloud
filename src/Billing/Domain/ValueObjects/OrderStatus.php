<?php

declare(strict_types=1);

namespace Billing\Domain\ValueObjects;

use JsonSerializable;

enum OrderStatus: string implements JsonSerializable
{
    case DRAFT = 'draft'; // Order created, but not paid
    case PENDING = 'pending'; // Pending payment
    case FAILED = 'failed'; // Payment failed
    case PROCESSING = 'processing'; // Paid, but not fulfilled
    case COMPLETED = 'completed'; // Paid and fulfilled
    case CANCELLED = 'cancelled'; // Cancelled by user or admin

    /** @return string  */
    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
