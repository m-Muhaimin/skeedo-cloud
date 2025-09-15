<?php

declare(strict_types=1);

namespace Presentation\Resources\Api;

use Ai\Domain\Entities\ImageEntity;
use JsonSerializable;
use Override;
use Presentation\Resources\DateTimeResource;

class ImageResource implements JsonSerializable
{
    use Traits\TwigResource;

    public function __construct(private ImageEntity $image) {}

    #[Override]
    public function jsonSerialize(): array
    {
        $i = $this->image;

        return [
            'object' => 'image',
            'id' => $i->getId(),
            'model' => $i->getModel(),
            'visibility' => $i->getVisibility(),
            'title' => $i->getTitle(),
            'cost' => $i->getCost(),
            'created_at' => new DateTimeResource($i->getCreatedAt()),
            'updated_at' => new DateTimeResource($i->getUpdatedAt()),
            'state' => $i->getState(),
            'progress' => $i->getProgress(),
            'params' => $i->getRequestParams(),
            'output_file' => $i->getOutputFile() ? new ImageFileResource($i->getOutputFile()) : null,
            'user' => new UserResource($i->getUser()),
            'meta' => $i->getMeta(),
        ];
    }
}
