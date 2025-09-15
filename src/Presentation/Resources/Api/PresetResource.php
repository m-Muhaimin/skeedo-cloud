<?php

declare(strict_types=1);

namespace Presentation\Resources\Api;

use JsonSerializable;
use Override;
use Presentation\Resources\DateTimeResource;
use Preset\Domain\Entities\PresetEntity;

class PresetResource implements JsonSerializable
{
    use Traits\TwigResource;

    public function __construct(
        private PresetEntity $preset
    ) {
    }

    #[Override]
    public function jsonSerialize(): array
    {
        $preset = $this->preset;

        $output = [
            'object' => 'preset',
            'id' => $preset->getId(),
            'type' => $preset->getType(),
            'title' => $preset->getTitle(),
            'description' => $preset->getDescription(),
            'image' => $preset->getImage(),
            'color' => $preset->getColor(),
            'created_at' => new DateTimeResource($preset->getCreatedAt()),
            'updated_at' => new DateTimeResource($preset->getUpdatedAt()),
            'category' => $preset->getCategory()
                ? new CategoryResource($preset->getCategory())
                : null,
        ];

        return $output;
    }
}
