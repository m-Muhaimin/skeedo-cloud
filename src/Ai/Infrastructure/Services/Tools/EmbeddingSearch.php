<?php


declare(strict_types=1);

namespace Ai\Infrastructure\Services\Tools;

use Ai\Domain\Embedding\EmbeddingServiceInterface;
use Ai\Domain\Services\AiServiceFactoryInterface;
use Ai\Domain\ValueObjects\Model;
use Ai\Infrastructure\Services\VectorSearch;
use Easy\Container\Attributes\Inject;
use Override;
use User\Domain\Entities\UserEntity;
use Workspace\Domain\Entities\WorkspaceEntity;

class EmbeddingSearch implements ToolInterface
{
    public const LOOKUP_KEY = 'embedding_search';

    public function __construct(
        private AiServiceFactoryInterface $factory,
        private VectorSearch $vectorSearch,

        #[Inject('option.features.tools.embedding_search.is_enabled')]
        private ?bool $isEnabled = null,
    ) {}

    #[Override]
    public function isEnabled(): bool
    {
        return $this->isEnabled ?? false;
    }

    #[Override]
    public function getDescription(): string
    {
        return 'Retrieves the information for the search query based on the file embeddings. Returns the most relevant results in JSON-encoded format.';
    }

    #[Override]
    public function getDefinitions(): array
    {
        return [
            "type" => "object",
            "properties" => [
                "query" => [
                    "type" => "string",
                    "description" => "Query to search the embeddings for."
                ],
            ],
            "required" => ["query"]
        ];
    }

    #[Override]
    public function call(
        UserEntity $user,
        WorkspaceEntity $workspace,
        array $params = [],
        array $files = [],
        array $knowledgeBase = [],
    ): CallResponse {
        $query = $params['query'];

        $model = new Model('text-embedding-3-small'); // Default model
        $sub = $workspace->getSubscription();
        if ($sub) {
            $model = $sub->getPlan()->getConfig()->embeddingModel;
        }

        $service = $this->factory->create(
            EmbeddingServiceInterface::class,
            $model
        );

        $resp = $service->generateEmbedding($model, $query);
        $searchVector = $resp->embedding->value[0]['embedding'];

        $embeddings = array_map(
            fn($file) => $file->getEmbedding()->value,
            $files
        );

        $results = $this->vectorSearch->searchVectors($searchVector, $embeddings);

        $texts = array_map(function ($r) {
            return $r['content'];
        }, $results);

        $content = json_encode($texts, JSON_INVALID_UTF8_SUBSTITUTE);
        if ($content === false) {
            $content = 'Failed to encode results: ' . json_last_error_msg();
        }

        return new CallResponse(
            $content,
            $resp->cost
        );
    }
}
