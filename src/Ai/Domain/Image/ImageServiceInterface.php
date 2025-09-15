<?php

declare(strict_types=1);

namespace Ai\Domain\Image;

use Ai\Domain\Entities\ImageEntity;
use Ai\Domain\Exceptions\ApiException;
use Ai\Domain\Exceptions\DomainException;
use Ai\Domain\Services\AiServiceInterface;
use Ai\Domain\ValueObjects\Model;
use User\Domain\Entities\UserEntity;
use Workspace\Domain\Entities\WorkspaceEntity;

interface ImageServiceInterface extends AiServiceInterface
{
    /**
     * @throws DomainException
     * @throws ApiException
     */
    public function generateImage(
        WorkspaceEntity $workspace,
        UserEntity $user,
        Model $model,
        ?array $params = null
    ): ImageEntity;
}
