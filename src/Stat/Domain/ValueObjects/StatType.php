<?php

declare(strict_types=1);

namespace Stat\Domain\ValueObjects;

use JsonSerializable;
use Override;

enum StatType: string implements JsonSerializable
{
    case USAGE = 'usage';
    case SIGNUP = 'signup';
    case SUBSCRIPTION = 'subscription';
    case ORDER = 'order';

    #[Override]
    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
