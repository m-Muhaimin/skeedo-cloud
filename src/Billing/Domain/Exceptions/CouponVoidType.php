<?php

declare(strict_types=1);

namespace Billing\Domain\Exceptions;

enum CouponVoidType: string
{
    case UNKNOWN = 'unknown';
    case INACTIVE = 'inactive';
    case EXPIRED = 'expired';
    case PREMATURE = 'premature';
    case PLAN_MISMATCH = 'plan_mismatch';
    case REDEMPTION_LIMIT = 'redemption_limit';
}
