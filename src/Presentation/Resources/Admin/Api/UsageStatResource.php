<?php

declare(strict_types=1);

namespace Presentation\Resources\Admin\Api;

use JsonSerializable;
use Presentation\Resources\DateTimeResource;
use Stat\Domain\Entities\UsageStatEntity;

class UsageStatResource implements JsonSerializable
{

    public function __construct(
        private UsageStatEntity $stat,
        private array $extend = []
    ) {}

    public function jsonSerialize(): array
    {
        $data = [
            'id' => $this->stat->getId(),
            'date' => new DateTimeResource($this->stat->getDate()),
            'metric' => $this->stat->getMetric(),
            'workspace' => $this->stat->getWorkspace()?->getId(),
        ];

        if (in_array('workspace', $this->extend)) {
            $data['workspace'] = new WorkspaceResource(
                $this->stat->getWorkspace()
            );
        }

        return $data;
    }
}
