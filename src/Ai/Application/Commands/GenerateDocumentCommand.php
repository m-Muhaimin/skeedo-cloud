<?php

declare(strict_types=1);

namespace Ai\Application\Commands;

use Ai\Application\CommandHandlers\GenerateDocumentCommandHandler;
use Ai\Domain\ValueObjects\Model;
use Preset\Domain\Entities\PresetEntity;
use Shared\Domain\ValueObjects\Id;
use Shared\Infrastructure\CommandBus\Attributes\Handler;
use User\Domain\Entities\UserEntity;
use Workspace\Domain\Entities\WorkspaceEntity;

#[Handler(GenerateDocumentCommandHandler::class)]
class GenerateDocumentCommand
{
    public Id|WorkspaceEntity $workspace;
    public Id|UserEntity $user;
    public Model $model;
    public string|Id|PresetEntity $prompt;
    public array $params = [];

    public function __construct(
        string|Id|WorkspaceEntity $workspace,
        string|Id|UserEntity $user,
        string|Model $model,
        string|Id|PresetEntity $prompt,
    ) {
        $this->workspace = is_string($workspace) ? new Id($workspace) : $workspace;
        $this->user = is_string($user) ? new Id($user) : $user;
        $this->model = is_string($model) ?  new Model($model) : $model;
        if (is_string($prompt)) {
            try {
                $this->prompt = new Id($prompt);
            } catch (\Throwable $th) {
                // Unable to create Id from string, not a valid UU
                $this->prompt = $prompt;
            }
        } else {
            $this->prompt = $prompt;
        }
    }

    public function param(string $key, mixed $value): self
    {
        $this->params[$key] = $value;
        return $this;
    }
}
