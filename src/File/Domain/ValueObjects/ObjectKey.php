<?php

declare(strict_types=1);

namespace File\Domain\ValueObjects;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Override;
use Shared\Domain\Exceptions\InvalidValueException;

#[ORM\Embeddable]
class ObjectKey implements JsonSerializable
{
    #[ORM\Column(type: Types::STRING, name: "object_key")]
    public readonly string $value;

    public function __construct(string $value)
    {
        $this->ensureValueIsValid($value);
        $this->value = $value;
    }

    #[Override]
    public function jsonSerialize(): string
    {
        return $this->value;
    }

    private function ensureValueIsValid(string $value): void
    {
        $pattern = '/^[a-z0-9\-_\/\.]+$/i';

        if (!preg_match_all($pattern, $value)) {
            throw new InvalidValueException(sprintf(
                '<%s> does not allow the value <%s>',
                static::class,
                $value
            ));
        }
    }
}
