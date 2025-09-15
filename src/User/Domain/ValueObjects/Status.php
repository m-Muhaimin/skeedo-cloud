<?php

declare(strict_types=1);

namespace User\Domain\ValueObjects;

use JsonSerializable;
use Override;

enum Status: int implements JsonSerializable
{
    case INACTIVE = 0;
    case ACTIVE = 1;

        // Following values are not saved, but used for filtering.
        // Both ONLINE and AWAY are active users.
    case ONLINE = 2; // Active and last seen within UserEntity::ONLINE_THRESHOLD
    case AWAY = 3; // Active, but last seen before UserEntity::ONLINE_THRESHOLD

    #[Override]
    public function jsonSerialize(): int
    {
        return $this->value;
    }
}
