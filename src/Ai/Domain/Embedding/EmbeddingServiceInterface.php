<?php

declare(strict_types=1);

namespace Ai\Domain\Embedding;

use Ai\Domain\Services\AiServiceInterface;
use Ai\Domain\ValueObjects\Model;

interface EmbeddingServiceInterface extends AiServiceInterface
{
    public function generateEmbedding(
        Model $model,
        string $text
    ): EmbeddingResponse;
}
