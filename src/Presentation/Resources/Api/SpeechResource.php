<?php

declare(strict_types=1);

namespace Presentation\Resources\Api;

use Ai\Domain\Entities\SpeechEntity;
use JsonSerializable;
use Override;
use Presentation\Resources\DateTimeResource;

class SpeechResource implements JsonSerializable
{
    use Traits\TwigResource;

    public function __construct(private SpeechEntity $speech)
    {
    }

    #[Override]
    public function jsonSerialize(): array
    {
        $i = $this->speech;

        return [
            'object' => 'speech',
            'id' => $i->getId(),
            'model' => $i->getModel(),
            'visibility' => $i->getVisibility(),
            'cost' => $i->getCost(),
            'created_at' => new DateTimeResource($i->getCreatedAt()),
            'updated_at' => new DateTimeResource($i->getUpdatedAt()),
            'params' => $i->getRequestParams(),
            'output_file' => new FileResource($i->getOutputFile()),
            'title' => $i->getTitle(),
            'content' => $i->getContent(),
            'voice' => $i->getVoice() ? new VoiceResource($i->getVoice()) : null,
            'user' => new UserResource($i->getUser()),
        ];
    }
}
