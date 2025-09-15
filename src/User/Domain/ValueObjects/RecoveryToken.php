<?php

declare(strict_types=1);

namespace User\Domain\ValueObjects;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Override;

#[ORM\Embeddable]
class RecoveryToken implements JsonSerializable
{
    #[ORM\Column(type: Types::STRING, name: "recovery_token", nullable: true)]
    public readonly ?string $value;

    public function __construct(?string $value = null)
    {
        $this->value =  $value;
    }

    #[Override]
    public function jsonSerialize(): ?string
    {
        return $this->value;
    }
}
