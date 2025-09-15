<?php

declare(strict_types=1);

namespace Preset\Domain\ValueObjects;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use JsonSerializable;
use Override;

#[ORM\Embeddable]
class Template implements JsonSerializable
{
    #[ORM\Column(type: Types::TEXT, name: "template", nullable: true)]
    public readonly ?string $value;

    public function __construct(?string $value = null)
    {
        $this->value = $value;
    }

    #[Override]
    public function jsonSerialize(): ?string
    {
        return $this->value;
    }
}
