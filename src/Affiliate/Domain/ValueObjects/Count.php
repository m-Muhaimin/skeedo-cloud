<?php

declare(strict_types=1);

namespace Affiliate\Domain\ValueObjects;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Override;
use Shared\Domain\Exceptions\InvalidValueException;

#[ORM\Embeddable]
class Count implements JsonSerializable
{
    #[ORM\Column(name: "count", type: Types::INTEGER)]
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

    /**
     * @throws InvalidValueException 
     */
    private function ensureValueIsValid(int  $value): void
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
