<?php

declare(strict_types=1);

namespace Assistant\Domain\ValueObjects;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Override;
use Shared\Domain\Exceptions\InvalidValueException;

#[ORM\Embeddable]
class AvatarUrl implements JsonSerializable
{
    #[ORM\Column(type: Types::TEXT, name: "avatar_url", nullable: true)]
    public readonly ?string $value;

    public function __construct(?string $value = null)
    {
        $this->ensureValueIsValid($value);
        $this->value = $value;
    }

    #[Override]
    public function jsonSerialize(): ?string
    {
        return $this->value;
    }

    private function ensureValueIsValid(?string $value): void
    {
        if (!is_null($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
            throw new InvalidValueException(sprintf(
                '<%s> does not allow the value <%s>.',
                static::class,
                $value
            ));
        }
    }
}
