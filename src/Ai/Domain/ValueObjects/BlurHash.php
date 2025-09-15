<?php

declare(strict_types=1);

namespace Ai\Domain\ValueObjects;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Override;

#[ORM\Embeddable]
class BlurHash implements JsonSerializable
{
    #[ORM\Column(type: Types::STRING, name: "blur_hash")]
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
