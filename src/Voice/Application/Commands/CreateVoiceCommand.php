<?php

declare(strict_types=1);

namespace Voice\Application\Commands;

use Ai\Domain\ValueObjects\Model;
use Ai\Domain\ValueObjects\Visibility;
use Psr\Http\Message\UploadedFileInterface;
use Shared\Domain\ValueObjects\Id;
use Shared\Infrastructure\CommandBus\Attributes\Handler;
use User\Domain\Entities\UserEntity;
use Voice\Application\CommandHandlers\CreateVoiceCommandHandler;
use Voice\Domain\ValueObjects\Name;
use Workspace\Domain\Entities\WorkspaceEntity;

#[Handler(CreateVoiceCommandHandler::class)]
class CreateVoiceCommand
{
    public Id|WorkspaceEntity $workspace;
    public Id|UserEntity $user;
    public Model $model;
    public Name $name;
    public Visibility $visibility = Visibility::PRIVATE;

    public function __construct(
        string|Id|WorkspaceEntity $workspace,
        string|Id|UserEntity $user,
        string|Model $model,
        string|Name $name,
        public UploadedFileInterface $file,
    ) {
        $this->workspace = is_string($workspace) ? new Id($workspace) : $workspace;
        $this->user = is_string($user) ? new Id($user) : $user;
        $this->model = is_string($model) ? new Model($model) : $model;
        $this->name = is_string($name) ? new Name($name) : $name;
    }

    public function setVisibility(int $visibility): void
    {
        $this->visibility = Visibility::from($visibility);
    }
}
