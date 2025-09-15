<?php

declare(strict_types=1);

namespace Ai\Domain\ValueObjects;

use JsonSerializable;
use Override;

class Call implements JsonSerializable
{
    public function __construct(
        public readonly string $name,
        public readonly array $params,
    ) {
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'params' => $this->params,
        ];
    }
}
