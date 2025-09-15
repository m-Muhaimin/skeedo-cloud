<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services\Tools;

use Ai\Domain\Entities\MemoryEntity;
use Ai\Domain\Repositories\LibraryItemRepositoryInterface;
use Ai\Domain\ValueObjects\Content;
use Ai\Domain\ValueObjects\Visibility;
use Billing\Domain\ValueObjects\CreditCount;
use Easy\Container\Attributes\Inject;
use Override;
use User\Domain\Entities\UserEntity;
use Workspace\Domain\Entities\WorkspaceEntity;

class SaveMemory implements ToolInterface
{
    public const LOOKUP_KEY = 'save_memory';

    public function __construct(
        private LibraryItemRepositoryInterface $repo,

        #[Inject('option.features.tools.memory.is_enabled')]
        private ?bool $isEnabled = null,
    ) {}

    #[Override]
    public function isEnabled(): bool
    {
        return (bool) $this->isEnabled;
    }

    #[Override]
    public function getDescription(): string
    {
        return 'Saves important information to user memory for future reference. Use this when the user provides information that should be remembered for future conversations. Each memory should be kept short and concise. If multiple pieces of information need to be saved, make separate calls to this tool for each memory to keep them organized and easily retrievable.';
    }

    #[Override]
    public function getDefinitions(): array
    {
        return [
            "type" => "object",
            "properties" => [
                "content" => [
                    "type" => "string",
                    "description" => "The information to save to memory."
                ]
            ],
            "required" => ["content"]
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
        $content = $params['content'];

        // Create and save memory entity
        $memory = new MemoryEntity(
            $workspace,
            $user,
            new Content($content),
            new CreditCount(0),
            Visibility::PRIVATE
        );

        $this->repo->add($memory);

        return new CallResponse(
            json_encode([
                'success' => true,
                'memory_id' => (string) $memory->getId()->getValue(),
                'message' => 'Information saved to memory successfully.'
            ], JSON_PRETTY_PRINT),
            new CreditCount(0)
        );
    }
}
