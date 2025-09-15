<?php

declare(strict_types=1);

namespace Billing\Domain\ValueObjects;

use JsonSerializable;

enum SubscriptionStatus: string implements JsonSerializable
{
    case ACTIVE = 'active';
    case TRIALING = 'trialing';
    case CANCELED = 'canceled';
    case ENDED = 'ended';
    case UNKNOWN = 'unknown';

    /** @return string  */
    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
