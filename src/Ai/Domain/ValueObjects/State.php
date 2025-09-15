<?php

declare(strict_types=1);

namespace Ai\Domain\ValueObjects;

use JsonSerializable;
use Override;

enum State: int implements JsonSerializable
{
    case DRAFT = 0;
    case QUEUED = 1;
    case PROCESSING = 2;
    case COMPLETED = 3;
    case FAILED = 4;

    #[Override]
    public function jsonSerialize(): int
    {
        return $this->value;
    }
}
