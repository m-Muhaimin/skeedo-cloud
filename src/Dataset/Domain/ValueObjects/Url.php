<?php

declare(strict_types=1);

namespace Dataset\Domain\ValueObjects;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use JsonSerializable;
use Override;

#[ORM\Embeddable]
class Url implements JsonSerializable
{
    #[ORM\Column(type: Types::TEXT, name: "url")]
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

    private function ensureValueIsValid(string $value)
    {
        if (filter_var($value, FILTER_VALIDATE_URL) === false) {
            throw new InvalidArgumentException(sprintf(
                '<%s> does not allow the value <%s>. Not a valid URL',
                static::class,
                $value
            ));
        }
    }
}
