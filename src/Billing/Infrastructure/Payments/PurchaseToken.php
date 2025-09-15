<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Payments;

class PurchaseToken
{
    public function __construct(
        public readonly string $value
    ) {
    }
}
