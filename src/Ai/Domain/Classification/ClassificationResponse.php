<?php

declare(strict_types=1);

namespace Ai\Domain\Classification;

use Ai\Domain\ValueObjects\Classification;
use Billing\Domain\ValueObjects\CreditCount;

class ClassificationResponse
{
    public function __construct(
        public readonly CreditCount $cost,
        public readonly Classification $classification,
    ) {}
}
