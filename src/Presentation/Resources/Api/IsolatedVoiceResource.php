<?php

declare(strict_types=1);

namespace Presentation\Resources\Api;

use Ai\Domain\Entities\IsolatedVoiceEntity;
use Ai\Domain\Entities\SpeechEntity;
use JsonSerializable;
use Override;
use Presentation\Resources\DateTimeResource;

class IsolatedVoiceResource implements JsonSerializable
{
    use Traits\TwigResource;

    public function __construct(private IsolatedVoiceEntity $voice)
    {
    }

    #[Override]
    public function jsonSerialize(): array
    {
        $i = $this->voice;

        return [
            'object' => 'isolated_voice',
            'id' => $i->getId(),
            'model' => $i->getModel(),
            'visibility' => $i->getVisibility(),
            'cost' => $i->getCost(),
            'created_at' => new DateTimeResource($i->getCreatedAt()),
            'updated_at' => new DateTimeResource($i->getUpdatedAt()),
            'params' => $i->getRequestParams(),
            'input_file' => new FileResource($i->getInputFile()),
            'output_file' => new FileResource($i->getOutputFile()),
            'title' => $i->getTitle(),
            'user' => new UserResource($i->getUser()),
        ];
    }
}
