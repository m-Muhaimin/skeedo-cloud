<?php

declare(strict_types=1);

namespace Ai\Application\Commands;

use Ai\Application\CommandHandlers\GenerateSpeechCommandHandler;
use Shared\Domain\ValueObjects\Id;
use Shared\Infrastructure\CommandBus\Attributes\Handler;
use User\Domain\Entities\UserEntity;
use Voice\Domain\Entities\VoiceEntity;
use Workspace\Domain\Entities\WorkspaceEntity;

#[Handler(GenerateSpeechCommandHandler::class)]
class GenerateSpeechCommand
{
    public Id|WorkspaceEntity $workspace;
    public Id|UserEntity $user;
    public Id|VoiceEntity $voice;
    public array $params = [];

    public function __construct(
        string|Id|WorkspaceEntity $workspace,
        string|Id|UserEntity $user,
        string|Id|VoiceEntity $voice,
    ) {
        $this->workspace = is_string($workspace) ? new Id($workspace) : $workspace;
        $this->user = is_string($user) ? new Id($user) : $user;
        $this->voice = is_string($voice) ? new Id($voice) : $voice;
    }

    public function param(string $key, mixed $value): self
    {
        $this->params[$key] = $value;
        return $this;
    }
}
