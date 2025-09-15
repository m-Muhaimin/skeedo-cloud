<?php

declare(strict_types=1);

namespace Presentation\Resources\Admin\Api;

use Dataset\Domain\Entities\FileUnitEntity;
use JsonSerializable;
use Override;
use Presentation\Resources\DateTimeResource;

class FileUnitResource implements JsonSerializable
{
    public function __construct(private FileUnitEntity $unit) {}

    #[Override]
    public function jsonSerialize(): array
    {
        $u = $this->unit;

        return [
            'object' => 'file_unit',
            'id' => $u->getId(),
            'title' => $u->getTitle(),
            'created_at' => new DateTimeResource($u->getCreatedAt()),
            'updated_at' => new DateTimeResource($u->getUpdatedAt()),
            'file' => new FileResource($u->getFile())
        ];
    }
}
