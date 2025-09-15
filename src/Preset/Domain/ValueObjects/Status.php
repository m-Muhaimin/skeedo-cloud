<?php

declare(strict_types=1);

namespace Preset\Domain\ValueObjects;

use JsonSerializable;
use Override;

enum Status: int implements JsonSerializable
{
    case INACTIVE = 0;
    case ACTIVE = 1;

    #[Override]
    public function jsonSerialize(): int
    {
        return $this->value;
    }
}
