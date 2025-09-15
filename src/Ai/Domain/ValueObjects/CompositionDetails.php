<?php

declare(strict_types=1);

namespace Ai\Domain\ValueObjects;

use JsonSerializable;
use Override;

class CompositionDetails implements JsonSerializable
{
    public function __construct(
        public ?string $lyrics = null,
        public ?string $tags = null
    ) {}

    #[Override]
    public function jsonSerialize(): array
    {
        return [
            'lyrics' => $this->lyrics,
            'tags' => $this->tags,
        ];
    }
}
