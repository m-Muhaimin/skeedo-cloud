<?php

declare(strict_types=1);

namespace Billing\Domain\ValueObjects\PlanConfig;

use Ai\Domain\ValueObjects\Model;
use JsonSerializable;
use Override;

class TitlerConfig implements JsonSerializable
{
    public function __construct(
        public readonly Model $model
    ) {
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return [
            'model' => $this->model
        ];
    }
}
