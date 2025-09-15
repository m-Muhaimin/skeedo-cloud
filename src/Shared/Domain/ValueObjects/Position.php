<?php

declare(strict_types=1);

namespace Shared\Domain\ValueObjects;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Override;
use Shared\Domain\Exceptions\InvalidValueException;

#[ORM\Embeddable]
class Position implements JsonSerializable
{
    #[ORM\Column(name: "position", type: Types::DECIMAL, precision: 21, scale: 20)]
    public readonly string $value;

    public function __construct(int|float $value = 1)
    {
        $this->ensureValueIsValid($value);
        $this->value = (string) $value;
    }

    #[Override]
    public function jsonSerialize(): int|float
    {
        $int = intval($this->value);
        $float = floatval($this->value);

        return $int == $float ? $int : $float;
    }

    /**
     * @throws InvalidValueException 
     */
    private function ensureValueIsValid(int|float  $value): void
    {
        if ($value < 0) {
            throw new InvalidValueException(sprintf(
                '<%s> does not allow the value <%s>. Value must greater than 0.',
                static::class,
                $value
            ));
        }
    }
}
