<?php

declare(strict_types=1);

namespace Plugin\Domain\ValueObjects;

use InvalidArgumentException;
use JsonSerializable;

class Title implements JsonSerializable
{
    public readonly ?string $value;

    public function __construct(?string $value = null)
    {
        $this->ensureValueIsValid($value);
        $this->value = $value;
    }

    public function jsonSerialize(): ?string
    {
        return $this->value;
    }

    private function ensureValueIsValid(?string $value): void
    {
        if (!is_null($value) && mb_strlen($value) > 80) {
            throw new InvalidArgumentException(sprintf(
                '<%s> does not allow the value <%s>. Maximum <%s> characters allowed.',
                static::class,
                $value,
                80
            ));
        }
    }
}
