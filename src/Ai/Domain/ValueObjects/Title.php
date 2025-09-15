<?php

declare(strict_types=1);

namespace Ai\Domain\ValueObjects;

use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Override;

#[ORM\Embeddable]
class Title implements JsonSerializable
{
    #[ORM\Column(type: 'string', name: "title", length: 255, nullable: true)]
    public readonly ?string $value;

    public function __construct(?string $value = null)
    {
        $this->value = is_string($value) ? mb_substr($value, 0, 255) : $value;
    }

    #[Override]
    public function jsonSerialize(): ?string
    {
        return $this->value;
    }
}
