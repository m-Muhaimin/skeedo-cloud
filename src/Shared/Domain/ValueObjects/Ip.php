<?php

declare(strict_types=1);

namespace Shared\Domain\ValueObjects;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Override;
use Shared\Domain\Exceptions\InvalidValueException;

#[ORM\Embeddable]
class Ip implements JsonSerializable
{
    #[ORM\Column(type: Types::STRING, name: "ip", nullable: true)]
    public readonly ?string $value;

    /**
     * @throws InvalidValueException
     */
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

    /**
     * @throws InvalidValueException
     */
    private function ensureValueIsValid(?string $value)
    {
        if (!is_null($value) && !filter_var($value, FILTER_VALIDATE_IP)) {
            throw new InvalidValueException(sprintf(
                '<%s> does not allow the value <%s>.',
                static::class,
                $value
            ));
        }
    }
}
