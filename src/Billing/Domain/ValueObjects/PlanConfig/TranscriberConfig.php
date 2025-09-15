<?php

declare(strict_types=1);

namespace Billing\Domain\ValueObjects\PlanConfig;

use JsonSerializable;
use Override;

class TranscriberConfig implements JsonSerializable
{
    public function __construct(
        public readonly bool $isEnabled
    ) {
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return [
            'is_enabled' => $this->isEnabled
        ];
    }
}
