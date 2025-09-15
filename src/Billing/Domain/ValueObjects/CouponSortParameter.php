<?php

declare(strict_types=1);

namespace Billing\Domain\ValueObjects;

enum CouponSortParameter: string
{
    case ID = 'id';
    case CREATED_AT = 'created_at';
    case UPDATED_AT = 'updated_at';
    case TITLE = 'title';
}
