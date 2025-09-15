<?php

declare(strict_types=1);

namespace Ai\Domain\IsolatedVoice;

use Billing\Domain\ValueObjects\CreditCount;
use Psr\Http\Message\StreamInterface;

class IsolatedVoiceResponse
{
    public function __construct(
        public readonly StreamInterface $audioContent,
        public readonly CreditCount $cost
    ) {}
}
