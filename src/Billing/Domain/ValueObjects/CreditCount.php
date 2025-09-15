<?php

declare(strict_types=1);

namespace Billing\Domain\ValueObjects;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Override;

#[ORM\Embeddable]
class CreditCount implements JsonSerializable
{
    #[ORM\Column(name: "count", type: Types::DECIMAL, precision: 23, scale: 11, nullable: true)]
    public readonly ?string $value;

    public function __construct(null|int|float $value = null)
    {
        $this->value = is_null($value) ? $value : (string) $value;
    }

    #[Override]
    public function jsonSerialize(): null|int|float
    {
        if (is_null($this->value)) {
            return null;
        }

        $int = intval($this->value);
        $float = floatval($this->value);

        return $int == $float ? $int : $float;
    }
}
