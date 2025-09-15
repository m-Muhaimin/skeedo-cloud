<?php

declare(strict_types=1);

namespace Billing\Domain\ValueObjects;

enum SubscriptionSortParameter: string
{
    case ID = 'id';
    case CREATED_AT = 'created_at';
    case UPDATED_AT = 'updated_at';
    case USAGE_COUNT = 'usage_count';
}
