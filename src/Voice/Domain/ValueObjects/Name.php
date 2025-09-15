<?php

declare(strict_types=1);

namespace Voice\Domain\ValueObjects;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Override;

#[ORM\Embeddable]
class Name implements JsonSerializable
{
    #[ORM\Column(type: Types::STRING, name: "name")]
    public readonly string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    #[Override]
    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
