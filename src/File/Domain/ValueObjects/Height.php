<?php

declare(strict_types=1);

namespace File\Domain\ValueObjects;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Override;
use Shared\Domain\Exceptions\InvalidValueException;

#[ORM\Embeddable]
class Height implements JsonSerializable
{
    #[ORM\Column(type: Types::INTEGER, name: "height")]
    public readonly int $value;

    public function __construct(int $value)
    {
        $this->ensureValueIsValid($value);
        $this->value = $value;
    }

    #[Override]
    public function jsonSerialize(): int
    {
        return $this->value;
    }

    private function ensureValueIsValid(int $value): void
    {
        if ($value < 0 || $value > PHP_INT_MAX) {
            throw new InvalidValueException(sprintf(
                '<%s> does not allow the value <%s>.',
                static::class,
                $value
            ));
        }
    }
}
