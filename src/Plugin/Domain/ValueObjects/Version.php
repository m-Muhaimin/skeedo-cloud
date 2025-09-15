<?php

declare(strict_types=1);

namespace Plugin\Domain\ValueObjects;

use InvalidArgumentException;
use JsonSerializable;

class Version implements JsonSerializable
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
        $regex = '#^([0-9]+)\.([0-9]+)\.([0-9]+)(?:-([0-9A-Za-z-]+(?:\.[0-9A-Za-z-]+)*))?(?:\+[0-9A-Za-z-]+)?$#';

        if (!is_null($value) && !preg_match_all($regex, $value)) {
            throw new InvalidArgumentException(sprintf(
                '<%s> does not allow the value <%s>.',
                static::class,
                $value
            ));
        }
    }
}
