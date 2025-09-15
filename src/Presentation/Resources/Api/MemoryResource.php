<?php

declare(strict_types=1);

namespace Presentation\Resources\Api;

use Ai\Domain\Entities\MemoryEntity;
use JsonSerializable;
use Override;
use Presentation\Resources\DateTimeResource;

class MemoryResource implements JsonSerializable
{
    use Traits\TwigResource;

    public function __construct(
        private MemoryEntity $memory
    ) {}

    #[Override]
    public function jsonSerialize(): array
    {
        $memory = $this->memory;

        return [
            'object' => 'memory',
            'id' => $memory->getId(),
            'visibility' => $memory->getVisibility(),
            'created_at' => new DateTimeResource($memory->getCreatedAt()),
            'updated_at' => new DateTimeResource($memory->getUpdatedAt()),
            'content' => $memory->getContent(),
            'user' => new UserResource($memory->getUser()),
        ];
    }
}
