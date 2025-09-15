<?php

declare(strict_types=1);

namespace Billing\Domain\ValueObjects\PlanConfig;

use JsonSerializable;
use Override;

class ChatConfig implements JsonSerializable
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
