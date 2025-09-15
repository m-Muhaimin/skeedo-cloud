<?php

declare(strict_types=1);

namespace Presentation\Resources\Api;

use JsonSerializable;
use Presentation\Resources\DateTimeResource;
use Workspace\Domain\Entities\WorkspaceInvitationEntity;

class WorkspaceInvitationResource implements JsonSerializable
{
    use Traits\TwigResource;

    public function __construct(
        private WorkspaceInvitationEntity $invitation
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->invitation->getId(),
            'email' => $this->invitation->getEmail(),
            'created_at' => new DateTimeResource($this->invitation->getCreatedAt()),
            'updated_at' => new DateTimeResource($this->invitation->getUpdatedAt()),
        ];
    }
}
