<?php

declare(strict_types=1);

namespace Presentation\Resources\Admin\Api;

use JsonSerializable;
use Workspace\Domain\Entities\WorkspaceInvitationEntity;

class WorkspaceInvitationResource implements JsonSerializable
{
    public function __construct(
        private WorkspaceInvitationEntity $invitation
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->invitation->getId(),
            'email' => $this->invitation->getEmail(),
            'created_at' => $this->invitation->getCreatedAt(),
            'updated_at' => $this->invitation->getUpdatedAt(),
        ];
    }
}
