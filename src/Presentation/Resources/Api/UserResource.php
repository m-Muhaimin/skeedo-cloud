<?php

declare(strict_types=1);

namespace Presentation\Resources\Api;

use Application;
use Exception;
use JsonSerializable;
use Presentation\Resources\CountryResource;
use Presentation\Resources\DateTimeResource;
use User\Domain\Entities\UserEntity;

class UserResource implements JsonSerializable
{
    use Traits\TwigResource;

    public function __construct(
        private UserEntity $user,
        private array $extend = []
    ) {}

    public function jsonSerialize(): array
    {
        $u = $this->user;

        $cap = $u->getWorkspaceCap()->value;

        if ($cap === 0) {
            $cap = Application::make('option.site.workspace_cap', null);

            if (is_string($cap) && $cap == '') {
                $cap = null;
            }
        }

        $data = [
            'id' => $u->getId(),
            'role' => $u->getRole()->value == 1 ? 'admin' : 'user',
            'email' => $u->getEmail(),
            'first_name' => $u->getFirstName(),
            'last_name' => $u->getLastName(),
            'phone_number' => $u->getPhoneNumber(),
            'language' => $u->getLanguage(),
            'has_password' => $u->hasPassword(),

            // Calculated amount of workspaces user can have
            'workspace_cap' => $cap,

            'avatar' => "https://www.gravatar.com/avatar/"
                . md5($u->getEmail()->value) . "?d=blank",

            'created_at' => new DateTimeResource($u->getCreatedAt()),
            'updated_at' => new DateTimeResource($u->getUpdatedAt()),
            'ip' => $u->getIp(),
            'country' => $u->getCountryCode()
                ? new CountryResource($u->getCountryCode()->value) : null,
            'city_name' => $u->getCityName(),
            'is_email_verified' => $u->isEmailVerified(),
            'api_key' => $u->getApiKey(),
            'affiliate' => new AffiliateResource($u->getAffiliate()),
            'workspace' => new WorkspaceResource(
                $u->getCurrentWorkspace()
            ),
            'preferences' => $u->getPreferences(),
        ];

        if (in_array('workspace', $this->extend)) {
            $data['workspaces'] = $this->getWorkspaces();
            $data['owned_workspaces'] = $this->getOwnedWorkspaces();
        }

        return $data;
    }

    /**
     * @return array
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

    private function getOwnedWorkspaces(): array
    {
        $workspaces = [];

        foreach ($this->user->getOwnedWorkspaces() as $wu) {
            $workspaces[] = new WorkspaceResource($wu);
        }

        return $workspaces;
    }
}
