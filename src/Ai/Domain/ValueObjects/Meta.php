<?php

declare(strict_types=1);

namespace Ai\Domain\ValueObjects;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Override;

#[ORM\Embeddable]
class Meta implements JsonSerializable
{
    #[ORM\Column(type: Types::JSON, name: 'meta', nullable: true)]
    public readonly ?array $data;

    public function __construct(?array $data = null)
    {
        $this->data = $data;
    }

    #[Override]
    public function jsonSerialize(): ?array
    {
        return $this->data;
    }
}
