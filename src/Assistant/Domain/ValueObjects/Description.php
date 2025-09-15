<?php

declare(strict_types=1);

namespace Assistant\Domain\ValueObjects;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Override;

#[ORM\Embeddable]
class Description implements JsonSerializable
{
    #[ORM\Column(type: Types::STRING, name: "description", length: 255, nullable: true)]
    public readonly ?string $value;

    public function __construct(?string $value = null)
    {
        $this->value = $value ? mb_substr($value, 0, 255) : null;
    }

    #[Override]
    public function jsonSerialize(): ?string
    {
        return $this->value;
    }
}
