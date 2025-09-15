<?php

declare(strict_types=1);

namespace Stat\Domain\ValueObjects;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Override;
use Shared\Domain\Exceptions\InvalidValueException;

#[ORM\Embeddable]
class Metric implements JsonSerializable
{
    #[ORM\Column(name: "metric", type: Types::DECIMAL, precision: 23, scale: 11, nullable: true)]
    public readonly string $value;

    public function __construct(int|float $value = 0)
    {
        $this->ensureValueIsValid($value);
        $this->value = (string) $value;
    }

    #[Override]
    public function jsonSerialize(): string
    {
        $int = intval($this->value);
        $float = floatval($this->value);

        return $int == $float ? (string) $int : $this->value;
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
