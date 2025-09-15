<?php

declare(strict_types=1);

namespace User\Domain\ValueObjects;

use JsonSerializable;
use Override;

enum Role: int implements JsonSerializable
{
    case USER = 0;
    case ADMIN = 1;

    #[Override]
    public function jsonSerialize(): int
    {
        return $this->value;
    }
}
