<?php

declare(strict_types=1);

namespace Ai\Domain\Video;

use Ai\Domain\Entities\VideoEntity;
use Ai\Domain\Services\AiServiceInterface;
use Ai\Domain\ValueObjects\Model;
use User\Domain\Entities\UserEntity;
use Workspace\Domain\Entities\WorkspaceEntity;

interface VideoServiceInterface extends AiServiceInterface
{
    public function generateVideo(
        WorkspaceEntity $workspace,
        UserEntity $user,
        Model $model,
        ?array $params = null
    ): VideoEntity;
}
