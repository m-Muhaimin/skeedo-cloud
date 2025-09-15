<?php

declare(strict_types=1);

namespace Billing\Domain\ValueObjects\PlanConfig;

use Ai\Domain\ValueObjects\Model;
use JsonSerializable;
use Override;

class WriterConfig implements JsonSerializable
{
    public function __construct(
        public readonly bool $isEnabled,
        public readonly Model $model
    ) {
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return [
            'is_enabled' => $this->isEnabled,
            'model' => $this->model
        ];
    }
}
