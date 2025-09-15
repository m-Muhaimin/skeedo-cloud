<?php

declare(strict_types=1);

namespace Presentation\Resources\Api;

use Ai\Domain\Entities\ClassificationEntity;
use JsonSerializable;
use Override;
use Presentation\Resources\DateTimeResource;

class ClassificationResource implements JsonSerializable
{
    use Traits\TwigResource;

    public function __construct(private ClassificationEntity $classification)
    {
    }

    #[Override]
    public function jsonSerialize(): array
    {
        $item = $this->classification;

        return [
            'object' => 'classification',
            'id' => $item->getId(),
            'model' => $item->getModel(),
            'visibility' => $item->getVisibility(),
            'cost' => $item->getCost(),
            'created_at' => new DateTimeResource($item->getCreatedAt()),
            'updated_at' => new DateTimeResource($item->getUpdatedAt()),
            'params' => $item->getRequestParams(),
            'content' => $item->getContent(),
            'title' => $item->getTitle(),
            'user' => new UserResource($item->getUser()),
        ];
    }
}
