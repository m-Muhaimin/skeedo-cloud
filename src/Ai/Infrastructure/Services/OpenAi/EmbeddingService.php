<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services\OpenAi;

use Ai\Domain\Embedding\EmbeddingResponse;
use Ai\Domain\Embedding\EmbeddingServiceInterface;
use Ai\Domain\Exceptions\ApiException;
use Ai\Domain\ValueObjects\Embedding;
use Ai\Domain\ValueObjects\EmbeddingMap;
use Ai\Domain\ValueObjects\Model;
use Ai\Infrastructure\Services\CostCalculator;
use Billing\Domain\ValueObjects\CreditCount;
use Override;
use Psr\Http\Client\ClientExceptionInterface;
use Traversable;

class EmbeddingService implements EmbeddingServiceInterface
{
    private const CHUNK_OVERLAP = 200; // Characters to overlap between chunks
    private const MAX_CHUNK_SIZE = 1000; // Maximum characters per chunk

    private array $models = [
        'text-embedding-3-large',
        'text-embedding-3-small',
        'text-embedding-ada-002'
    ];

    public function __construct(
        private Client $client,
        private CostCalculator $calc
    ) {}

    #[Override]
    public function supportsModel(Model $model): bool
    {
        return in_array($model->value, $this->models);
    }

    #[Override]
    public function getSupportedModels(): Traversable
    {
        foreach ($this->models as $model) {
            yield new Model($model);
        }
    }

    public function generateEmbedding(Model $model, string $text): EmbeddingResponse
    {
        // If text is not valid UTF-8, attempt to fix it by transliterating where possible
        if (!mb_check_encoding($text, 'UTF-8')) {
            $text = iconv('UTF-8', 'UTF-8//TRANSLIT//IGNORE', $text);
        }

        $chunks = $this->createOverlappingChunks($text);
        $tokens = 0;
        $maps = [];

        // Process chunks in batches of 1000
        $groups = array_chunk($chunks, 1000);

        foreach ($groups as $group) {
            $group = array_values(array_filter($group));

            if (!$group) {
                continue;
            }

            try {
                $resp = $this->client->sendRequest('POST', '/v1/embeddings', [
                    'model' => $model->value,
                    'input' => $group,
                ]);
            } catch (ClientExceptionInterface $th) {
                throw new ApiException($th->getMessage(), previous: $th);
            }

            $json = json_decode($resp->getBody()->getContents());

            if ($resp->getStatusCode() !== 200) {
                throw new ApiException($json->error->message);
            }

            foreach ($json->data as $data) {
                $maps[] = new EmbeddingMap(
                    $group[$data->index],
                    $data->embedding
                );
            }

            $tokens += $json->usage->total_tokens;
        }

        if ($this->client->hasCustomKey()) {
            $cost = new CreditCount(0);
        } else {
            $cost = $this->calc->calculate($tokens, $model);
        }

        return new EmbeddingResponse(
            new Embedding(...$maps),
            $cost
        );
    }

    private function createOverlappingChunks(string $text): array
    {
        $chunks = [];
        $paragraphs = $this->splitIntoParagraphs($text);
        $currentChunk = '';

        foreach ($paragraphs as $paragraph) {
            // Check if adding this paragraph would exceed chunk size
            if (mb_strlen($currentChunk . "\n" . $paragraph) > self::MAX_CHUNK_SIZE) {
                $chunks[] = trim($currentChunk);
                // Start new chunk with overlap from previous chunk
                $currentChunk = $this->getOverlap($currentChunk) . $paragraph;
            } else {
                $currentChunk .= ($currentChunk === '' ? '' : "\n") . $paragraph;
            }
        }

        // Add the last chunk if not empty
        if (trim($currentChunk) !== '') {
            $chunks[] = trim($currentChunk);
        }

        return $chunks;
    }

    private function splitIntoParagraphs(string $text): array
    {
        $result = [];

        // Split on single newlines
        $paragraphs = explode("\n", $text);

        // Process each line
        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if ($paragraph === '') {
                continue;
            }

            // If a single line is too long, split it by words
            if (mb_strlen($paragraph) > self::MAX_CHUNK_SIZE) {
                $words = explode(' ', $paragraph);
                $tempLine = '';

                foreach ($words as $word) {
                    if (mb_strlen($tempLine . ' ' . $word) > self::MAX_CHUNK_SIZE) {
                        $result[] = trim($tempLine);
                        $tempLine = $word;
                    } else {
                        $tempLine .= ($tempLine === '' ? '' : ' ') . $word;
                    }
                }

                if ($tempLine !== '') {
                    $result[] = trim($tempLine);
                }
            } else {
                $result[] = $paragraph;
            }
        }

        return $result;
    }

    private function getOverlap(string $text): string
    {
        // Get the last N characters of text, breaking at word boundaries
        $words = explode(' ', $text);
        $overlap = '';

        for ($i = count($words) - 1; $i >= 0; $i--) {
            $temp = $words[$i] . ' ' . $overlap;
            if (mb_strlen($temp) > self::CHUNK_OVERLAP) {
                break;
            }
            $overlap = $temp;
        }

        return trim($overlap) . "\n";
    }
}
