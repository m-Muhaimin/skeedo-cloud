<?php

declare(strict_types=1);

namespace Plugin\Domain\ValueObjects;

use JsonSerializable;

enum Type: string implements JsonSerializable
{
    case PLUGIN = 'plugin';
    case THEME = 'theme';

    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
