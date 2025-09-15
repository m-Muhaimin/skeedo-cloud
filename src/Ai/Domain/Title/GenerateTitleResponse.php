<?php

namespace Ai\Domain\Title;

use Ai\Domain\ValueObjects\Title;
use Billing\Domain\ValueObjects\CreditCount;

class GenerateTitleResponse
{
    public function __construct(
        public readonly Title $title,
        public readonly CreditCount $cost
    ) {}
}
