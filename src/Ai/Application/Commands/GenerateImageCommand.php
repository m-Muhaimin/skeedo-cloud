<?php

declare(strict_types=1);

namespace Ai\Application\Commands;

use Ai\Application\CommandHandlers\GenerateImageCommandHandler;
use Ai\Domain\ValueObjects\Model;
use Psr\Http\Message\UploadedFileInterface;
use Shared\Domain\ValueObjects\Id;
use Shared\Infrastructure\CommandBus\Attributes\Handler;
use User\Domain\Entities\UserEntity;
use Workspace\Domain\Entities\WorkspaceEntity;

#[Handler(GenerateImageCommandHandler::class)]
class GenerateImageCommand
{
    public Id|WorkspaceEntity $workspace;
    public Id|UserEntity $user;
    public Model $model;
    public array $params = [];

    public function __construct(
        string|Id|WorkspaceEntity $workspace,
        string|Id|UserEntity $user,
        string $model,
    ) {
        $this->workspace = is_string($workspace) ? new Id($workspace) : $workspace;
        $this->user = is_string($user) ? new Id($user) : $user;
        $this->model = new Model($model);
    }

    public function param(string $key, mixed $value): self
    {
        $this->params[$key] = $value;
        return $this;
    }

    public function image(UploadedFileInterface $image): self
    {
        if (!isset($this->params['images'])) {
            $this->params['images'] = [];
        }

        $this->params['images'][] = $image;
        return $this;
    }
}
