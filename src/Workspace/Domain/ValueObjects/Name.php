<?php

declare(strict_types=1);

namespace Workspace\Domain\ValueObjects;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Shared\Domain\Exceptions\InvalidValueException;

#[ORM\Embeddable]
class Name implements JsonSerializable
{
    #[ORM\Column(type: Types::STRING, name: "name")]
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

    private function ensureValueIsValid(string $value)
    {
        if (mb_strlen($value) > 50) {
            throw new InvalidValueException(sprintf(
                '<%s> does not allow the value <%s>. Maximum <%s> characters allowed.',
                static::class,
                $value,
                50
            ));
        }
    }
}
