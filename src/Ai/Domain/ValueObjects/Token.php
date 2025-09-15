<?php

declare(strict_types=1);

namespace Ai\Domain\ValueObjects;

use JsonSerializable;
use Stringable;

class Token implements JsonSerializable, Stringable
{
    public function __construct(
        public readonly string $value
    ) {
    }

    public function jsonSerialize(): mixed
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
