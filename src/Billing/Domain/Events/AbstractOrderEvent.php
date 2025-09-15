<?php

declare(strict_types=1);

namespace Billing\Domain\Events;

use Billing\Domain\Entities\OrderEntity;

abstract class AbstractOrderEvent
{
    public function __construct(
        public readonly OrderEntity $order,
    ) {
    }
}
