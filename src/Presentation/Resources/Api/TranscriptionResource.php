<?php

declare(strict_types=1);

namespace Presentation\Resources\Api;

use Ai\Domain\Entities\TranscriptionEntity;
use JsonSerializable;
use Override;
use Presentation\Resources\DateTimeResource;

class TranscriptionResource implements JsonSerializable
{
    use Traits\TwigResource;

    public function __construct(private TranscriptionEntity $transcription)
    {
    }

    #[Override]
    public function jsonSerialize(): array
    {
        $tr = $this->transcription;

        return [
            'object' => 'transcription',
            'id' => $tr->getId(),
            'model' => $tr->getModel(),
            'visibility' => $tr->getVisibility(),
            'cost' => $tr->getCost(),
            'created_at' => new DateTimeResource($tr->getCreatedAt()),
            'updated_at' => new DateTimeResource($tr->getUpdatedAt()),
            'params' => $tr->getRequestParams(),
            'input_file' => new FileResource($tr->getInputFile()),
            'content' => $tr->getContent(),
            'title' => $tr->getTitle(),
            'user' => new UserResource($tr->getUser()),
        ];
    }
}
