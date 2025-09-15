<?php

declare(strict_types=1);

namespace Ai\Domain\Embedding;

use Ai\Domain\ValueObjects\Embedding;
use Billing\Domain\ValueObjects\CreditCount;

class EmbeddingResponse
{
    public function __construct(
        public readonly Embedding $embedding,
        public readonly CreditCount $cost,
    ) {}
}
