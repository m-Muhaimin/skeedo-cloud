<?php

declare(strict_types=1);

namespace Presentation\Resources\Admin\Api;

use Voice\Domain\Entities\VoiceEntity;
use JsonSerializable;
use Override;
use Presentation\Resources\DateTimeResource;

class VoiceResource implements JsonSerializable
{
    public function __construct(
        private VoiceEntity $voice
    ) {}

    #[Override]
    public function jsonSerialize(): array
    {
        $e = $this->voice;

        return [
            'id' => $e->getId(),
            'status' => $e->getStatus(),
            'provider' => $e->getProvider(),
            'model' => $e->getModel(),
            'external_id' => $e->getExternalId(),
            'name' => $e->getName(),
            'sample_url' => $e->getSampleUrl(),
            'visibility' => $e->getVisibility(),
            'tones' => $e->getTones(),
            'use_cases' => $e->getUseCases(),
            'gender' => $e->getGender(),
            'accent' => $e->getAccent(),
            'age' => $e->getAge(),
            'created_at' => new DateTimeResource($e->getCreatedAt()),
            'updated_at' => new DateTimeResource($e->getUpdatedAt()),
            'supported_languages' => $e->getSupportedLanguages(),
            'user' => $e->getUser() ? new UserResource($e->getUser()) : null,
            'workspace' => $e->getWorkspace() ? new WorkspaceResource($e->getWorkspace()) : null,
        ];
    }
}
