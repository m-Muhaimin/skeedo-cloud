<?php

declare(strict_types=1);

namespace Ai\Application\Commands;

use Ai\Application\CommandHandlers\GenerateMessageCommandHandler;
use Ai\Domain\Entities\ConversationEntity;
use Ai\Domain\Entities\MessageEntity;
use Ai\Domain\ValueObjects\Content;
use Ai\Domain\ValueObjects\Model;
use Ai\Domain\ValueObjects\Quote;
use Assistant\Domain\Entities\AssistantEntity;
use Psr\Http\Message\UploadedFileInterface;;

use Shared\Domain\ValueObjects\Id;
use Shared\Infrastructure\CommandBus\Attributes\Handler;
use User\Domain\Entities\UserEntity;
use Workspace\Domain\Entities\WorkspaceEntity;

#[Handler(GenerateMessageCommandHandler::class)]
class GenerateMessageCommand
{
    public Id|WorkspaceEntity $workspace;
    public Id|UserEntity $user;
    public Id|ConversationEntity $conversation;
    public Model $model;
    public ?Content $prompt = null;
    public ?Quote $quote = null;
    public null|Id|AssistantEntity $assistant = null;
    public null|Id|MessageEntity $parent = null;

    public ?UploadedFileInterface $file = null; // Image file

    public function __construct(
        string|Id|WorkspaceEntity $workspace,
        string|Id|UserEntity $user,
        string|Id|ConversationEntity $conversation,
        string|Model $model,
    ) {
        $this->workspace = is_string($workspace) ? new Id($workspace) : $workspace;
        $this->user = is_string($user) ? new Id($user) : $user;
        $this->conversation = is_string($conversation) ? new Id($conversation) : $conversation;
        $this->model = is_string($model) ? new Model($model) : $model;
    }

    public function setPrompt(string|Content $prompt): void
    {
        $this->prompt = is_string($prompt) ? new Content($prompt) : $prompt;
    }

    public function setAssistant(string|Id|AssistantEntity $assistant): self
    {
        $this->assistant = is_string($assistant) ? new Id($assistant) : $assistant;
        return $this;
    }

    public function setQuote(string $quote): void
    {
        $this->quote = new Quote($quote);
    }

    public function setParent(string $parent): void
    {
        $this->parent = new Id($parent);
    }
}
