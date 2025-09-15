<?php

declare(strict_types=1);

namespace Affiliate\Domain\ValueObjects;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Override;
use Random\RandomException;

#[ORM\Embeddable]
class Code implements JsonSerializable
{
    #[ORM\Column(type: Types::STRING, name: "code", unique: true)]
    public readonly string $value;

    /**
     * @throws RandomException
     */
    public function __construct(?string $value = null)
    {
        if (is_null($value)) {
            $value = bin2hex(random_bytes(6));
        }

        $this->value = $value;
    }

    #[Override]
    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
