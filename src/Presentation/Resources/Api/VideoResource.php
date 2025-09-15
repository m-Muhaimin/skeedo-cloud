<?php

declare(strict_types=1);

namespace Presentation\Resources\Api;

use Ai\Domain\Entities\VideoEntity;
use JsonSerializable;
use Override;
use Presentation\Resources\DateTimeResource;

class VideoResource implements JsonSerializable
{
    use Traits\TwigResource;

    public function __construct(private VideoEntity $video) {}

    #[Override]
    public function jsonSerialize(): array
    {
        $v = $this->video;

        return [
            'object' => 'video',
            'id' => $v->getId(),
            'model' => $v->getModel(),
            'visibility' => $v->getVisibility(),
            'title' => $v->getTitle(),
            'cost' => $v->getCost(),
            'created_at' => new DateTimeResource($v->getCreatedAt()),
            'updated_at' => new DateTimeResource($v->getUpdatedAt()),
            'state' => $v->getState(),
            'progress' => $v->getProgress(),
            'params' => $v->getRequestParams(),
            'output_file' => $v->getOutputFile() ? new FileResource($v->getOutputFile()) : null,
            'cover_image' => $v->getCoverImage() ? new ImageFileResource($v->getCoverImage()) : null,
            'user' => new UserResource($v->getUser()),
            'meta' => $v->getMeta(),
        ];
    }
}
