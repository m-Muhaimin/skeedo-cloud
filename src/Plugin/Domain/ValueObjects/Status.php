<?php

declare(strict_types=1);

namespace Plugin\Domain\ValueObjects;

use JsonSerializable;

enum Status: string implements JsonSerializable
{
    case INACTIVE = 'inactive';
    case ACTIVE = 'active';

    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
