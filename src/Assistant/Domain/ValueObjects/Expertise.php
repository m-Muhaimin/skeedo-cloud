<?php

declare(strict_types=1);

namespace Assistant\Domain\ValueObjects;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Override;

#[ORM\Embeddable]
class Expertise implements JsonSerializable
{
    #[ORM\Column(type: Types::STRING, name: "expertise", length: 128, nullable: true)]
    public readonly ?string $value;

    public function __construct(?string $value = null)
    {
        $this->value = is_null($value) ? null : mb_substr($value, 0, 128);
    }

    #[Override]
    public function jsonSerialize(): ?string
    {
        return $this->value;
    }
}
