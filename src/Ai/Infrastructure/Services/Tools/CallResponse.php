<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services\Tools;

use Ai\Domain\Entities\AbstractLibraryItemEntity;
use Billing\Domain\ValueObjects\CreditCount;

class CallResponse
{
    public function __construct(
        public readonly string $content,
        public readonly CreditCount $cost,
        public readonly ?AbstractLibraryItemEntity $item = null,
    ) {}
}
