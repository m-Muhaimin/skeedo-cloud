<?php

declare(strict_types=1);

namespace Voice\Domain\ValueObjects;

use JsonSerializable;

enum Gender: string implements JsonSerializable
{
    case MALE = 'male';
    case FEMALE = 'female';
    case NEUTRAL = 'neutral';

    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
