<?php

declare(strict_types=1);

namespace Presentation\Resources\Api;

use Ai\Domain\Entities\CompositionEntity;
use JsonSerializable;
use Override;
use Presentation\Resources\DateTimeResource;

class CompositionResource implements JsonSerializable
{
    use Traits\TwigResource;

    public function __construct(private CompositionEntity $composition) {}

    #[Override]
    public function jsonSerialize(): array
    {
        $i = $this->composition;

        return [
            'object' => 'composition',
            'id' => $i->getId(),
            'model' => $i->getModel(),
            'visibility' => $i->getVisibility(),
            'cost' => $i->getCost(),
            'created_at' => new DateTimeResource($i->getCreatedAt()),
            'updated_at' => new DateTimeResource($i->getUpdatedAt()),
            'params' => $i->getRequestParams(),
            'title' => $i->getTitle(),
            'lyrics' => $i->getLyrics(),
            'tags' => $i->getTags(),
            'user' => new UserResource($i->getUser()),
            'output_file' => new FileResource($i->getOutputFile()),
            'cover_image' => $i->getCoverImage() ? new ImageFileResource($i->getCoverImage()) : null,
        ];
    }
}
