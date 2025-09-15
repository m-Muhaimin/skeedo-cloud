<?php

declare(strict_types=1);

namespace Shared\Domain\ValueObjects;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Override;
use Shared\Domain\Exceptions\InvalidValueException;

#[ORM\Embeddable]
class CityName implements JsonSerializable
{
    #[ORM\Column(type: Types::STRING, name: "city_name", nullable: true)]
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
        if (!is_null($value) && mb_strlen($value) > 150) {
            throw new InvalidValueException(sprintf(
                '<%s> does not allow the value <%s>. Maximum 150 characters allowed.',
                static::class,
                $value
            ));
        }
    }
}
