<?php

declare(strict_types=1);

namespace Ai\Domain\ValueObjects;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Override;

#[ORM\Embeddable]
class Embedding implements JsonSerializable
{
    /** @var array<array{content:string,embedding:array<float>}> */
    #[ORM\Column(type: Types::JSON, name: "embedding", nullable: true)]
    public readonly ?array $value;

    public function __construct(EmbeddingMap ...$maps)
    {
        $value = [];

        foreach ($maps as $map) {
            $value[] = [
                'content' => $map->content,
                'embedding' => $map->embedding,
            ];
        }

        $this->value = $value;
    }

    #[Override]
    public function jsonSerialize(): ?array
    {
        return $this->value;
    }
}
