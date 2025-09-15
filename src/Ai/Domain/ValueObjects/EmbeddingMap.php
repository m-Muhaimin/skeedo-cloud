<?php

namespace Ai\Domain\ValueObjects;

class EmbeddingMap
{
    public function __construct(
        public readonly string $content,
        public readonly array $embedding
    ) {
    }
}
