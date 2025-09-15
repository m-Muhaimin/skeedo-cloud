<?php

declare(strict_types=1);

namespace Presentation\Resources\Admin\Api;

use Exception;
use JsonSerializable;
use Presentation\Resources\CountryResource;
use Presentation\Resources\DateTimeResource;
use User\Domain\Entities\UserEntity;

class UserResource implements JsonSerializable
{
    public function __construct(
        private UserEntity $user,
        private array $extend = []
    ) {}

    public function jsonSerialize(): array
    {
        $u = $this->user;

        $data = [
            'id' => $u->getId(),
            'role' => $u->getRole(),
            'email' => $u->getEmail(),
            'first_name' => $u->getFirstName(),
            'last_name' => $u->getLastName(),
            'phone_number' => $u->getPhoneNumber(),
            'language' => $u->getLanguage(),
            'has_password' => $u->hasPassword(),
            'workspace_cap' => $u->getWorkspaceCap(),

            'avatar' => "https://www.gravatar.com/avatar/" .
                md5($u->getEmail()->value) . "?d=blank",

            'status' => $u->getStatus(),
            'created_at' => new DateTimeResource($u->getCreatedAt()),
            'updated_at' => new DateTimeResource($u->getUpdatedAt()),
            'last_seen_at' => new DateTimeResource($u->getLastSeenAt()),
            'is_online' => $u->isOnline(),
            'ip' => $u->getIp(),
            'country' => $u->getCountryCode()
                ? new CountryResource($u->getCountryCode()->value) : null,
            'city_name' => $u->getCityName(),

            'is_email_verified' => $u->isEmailVerified(),

            'workspace' => new WorkspaceResource(
                $u->getCurrentWorkspace()
            ),
            'referred_by' => $u->getReferredBy()
                ? $u->getReferredBy()->getId()
                : null,
        ];

        if (in_array('workspace', $this->extend)) {
            $data['workspaces'] = $this->getWorkspaces();
            $data['owned_workspaces'] = $this->getOwnedWorkspaces();
        }

        if (in_array('affiliate', $this->extend)) {
            $extend = array_filter($this->extend, fn($item) => str_starts_with($item, 'affiliate.'));
            $extend = array_map(fn($item) => substr($item, 10), $extend);

            $data['affiliate'] = new AffiliateResource($u->getAffiliate(), $extend);
        }

        if (in_array('ref', $this->extend)) {
            $data['referred_by'] = $u->getReferredBy()
                ? new UserResource($u->getReferredBy())
                : null;
        }

        return $data;
    }

    /**
     * @return WorkspaceResource[]
     * @throws Exception
     */
    private function getWorkspaces(): array
    {
        $workspaces = [];

        foreach ($this->user->getWorkspaces() as $wu) {
            $workspaces[] = new WorkspaceResource($wu);
        }

        return $workspaces;
    }

    /**
     * @return WorkspaceResource[]
     * @throws Exception
     */
    private function getOwnedWorkspaces(): array
    {
        $workspaces = [];

        foreach ($this->user->getOwnedWorkspaces() as $wu) {
            $workspaces[] = new WorkspaceResource($wu);
        }

        return $workspaces;
    }
}
