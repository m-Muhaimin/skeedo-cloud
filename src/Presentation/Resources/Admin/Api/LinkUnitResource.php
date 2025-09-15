<?php

declare(strict_types=1);

namespace Presentation\Resources\Admin\Api;

use Dataset\Domain\Entities\LinkUnitEntity;
use JsonSerializable;
use Override;
use Presentation\Resources\DateTimeResource;

class LinkUnitResource implements JsonSerializable
{
    public function __construct(private LinkUnitEntity $unit) {}

    #[Override]
    public function jsonSerialize(): array
    {
        $u = $this->unit;

        return [
            'object' => 'link_unit',
            'id' => $u->getId(),
            'title' => $u->getTitle(),
            'created_at' => new DateTimeResource($u->getCreatedAt()),
            'updated_at' => new DateTimeResource($u->getUpdatedAt()),
            'url' => $u->getUrl(),
            'has_embedding' => (bool) $u->getEmbedding()->value,
        ];
    }
}
