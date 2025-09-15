<?php

declare(strict_types=1);

namespace Presentation\Resources\Api;

use Ai\Domain\Entities\DocumentEntity;
use JsonSerializable;
use Override;
use Presentation\Resources\DateTimeResource;

class DocumentResource implements JsonSerializable
{
    use Traits\TwigResource;

    public function __construct(
        private DocumentEntity $document
    ) {
    }

    #[Override]
    public function jsonSerialize(): array
    {
        $document = $this->document;

        return [
            'object' => 'document',
            'id' => $document->getId(),
            'model' => $document->getModel(),
            'visibility' => $document->getVisibility(),
            'cost' => $document->getCost(),
            'created_at' => new DateTimeResource($document->getCreatedAt()),
            'updated_at' => new DateTimeResource($document->getUpdatedAt()),
            'params' => $document->getRequestParams(),

            'title' => $document->getTitle(),
            'content' => $document->getContent(),
            'preset' => $document->getPreset()
                ? new PresetResource($document->getPreset())
                : null,

            'user' => new UserResource($document->getUser()),
        ];
    }
}
