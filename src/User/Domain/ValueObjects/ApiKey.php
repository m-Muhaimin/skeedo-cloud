<?php

declare(strict_types=1);

namespace User\Domain\ValueObjects;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Override;
use Random\RandomException;

#[ORM\Embeddable]
class ApiKey implements JsonSerializable
{
    #[ORM\Column(type: Types::STRING, name: "api_key_hash", nullable: true, unique: true)]
    public readonly ?string $hash;

    #[ORM\Column(type: Types::STRING, name: "api_key_mask", nullable: true)]
    public readonly ?string $mask;

    private ?string $key = null;

    /**
     * @throws RandomException
     */
    public function __construct(?string $key = null)
    {
        if (!$key) {
            $key = bin2hex(random_bytes(32));
        }

        $this->hash = sha1($key);

        // Mask the key in a way that only the first 4 and last 4 characters are visible
        $this->mask = substr($key, 0, 4) . str_repeat('*', 24) . substr($key, -4);
        $this->key = $key;
    }

    #[Override]
    public function jsonSerialize(): ?string
    {
        return $this->key ?: $this->mask;
    }
}
