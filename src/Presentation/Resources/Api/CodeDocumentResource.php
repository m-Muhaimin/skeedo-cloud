<?php

declare(strict_types=1);

namespace Presentation\Resources\Api;

use Ai\Domain\Entities\CodeDocumentEntity;
use JsonSerializable;
use Override;
use Presentation\Resources\DateTimeResource;

class CodeDocumentResource implements JsonSerializable
{
    use Traits\TwigResource;

    public function __construct(
        private CodeDocumentEntity $document
    ) {
    }

    #[Override]
    public function jsonSerialize(): array
    {
        $document = $this->document;

        return [
            'object' => 'code_document',
            'id' => $document->getId(),
            'model' => $document->getModel(),
            'visibility' => $document->getVisibility(),
            'cost' => $document->getCost(),
            'created_at' => new DateTimeResource($document->getCreatedAt()),
            'updated_at' => new DateTimeResource($document->getUpdatedAt()),
            'params' => $document->getRequestParams(),

            'title' => $document->getTitle(),
            'content' => $document->getContent(),
            'user' => new UserResource($document->getUser()),
        ];
    }
}
