<?php

declare(strict_types=1);

namespace Plugin\Domain\ValueObjects;

use InvalidArgumentException;

class EntryClass
{
    public readonly ?string $value;

    public function __construct(?string $value = null)
    {
        $this->ensureValueIsValid($value);
        $this->value = $value;
    }

    private function ensureValueIsValid(?string $value): void
    {
        if (!is_null($value) && mb_strlen($value) < 1) {
            throw new InvalidArgumentException(sprintf(
                '<%s> does not allow the value <%s>. Minimum <%s> characters is required.',
                static::class,
                $value,
                1
            ));
        }
    }
}
