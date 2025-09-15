<?php

declare(strict_types=1);

namespace Plugin\Domain\ValueObjects;

use InvalidArgumentException;
use JsonSerializable;

class Name implements JsonSerializable
{
    public readonly string $value;

    public function __construct(string $value)
    {
        $this->ensureValueIsValid($value);
        $this->value = $value;
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }

    private function ensureValueIsValid(string $value): void
    {
        $regex = '#^[a-z0-9]([_.-]?[a-z0-9]+)*'
            . '/[a-z0-9](([_.]?|-{0,2})[a-z0-9]+)*$#';

        if (!preg_match_all($regex, $value)) {
            throw new InvalidArgumentException(sprintf(
                '<%s> does not allow the value <%s>.',
                static::class,
                $value
            ));
        }
    }
}
