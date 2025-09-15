<?php

declare(strict_types=1);

namespace Presentation\Resources\Api;

use Exception;
use JsonSerializable;
use Presentation\Resources\DateTimeResource;
use Workspace\Domain\Entities\WorkspaceEntity;

class WorkspaceResource implements JsonSerializable
{
    use Traits\TwigResource;

    public function __construct(
        private WorkspaceEntity $ws,
        private array $extend = []
    ) {}

    public function jsonSerialize(): array
    {
        $ws = $this->ws;
        $sub = $ws->getSubscription();

        $data = [
            'id' => $ws->getId(),
            'name' => $ws->getName(),
            'openai_api_key' => $ws->getOpenaiApiKey()->value,
            'anthropic_api_key' => $ws->getAnthropicApiKey()->value,
            'address' => $ws->getAddress(),
            'is_eligible_for_trial' => $ws->isEligibleForTrial(),
            'is_eligible_for_free_plan' => $ws->isEligibleForFreePlan(),
            'created_at' => new DateTimeResource($ws->getCreatedAt()),
            'updated_at' => new DateTimeResource($ws->getUpdatedAt()),
            'invitations' => $this->getInvitations(),
            'credit_count' => $ws->getCreditCount(),
            'total_credit_count' => $ws->getTotalCreditCount(),
            'subscription' => $sub ? new SubscriptionResource($sub) : null,
        ];

        if (in_array('user', $this->extend)) {
            $data['owner'] = new UserResource($ws->getOwner());
            $data['users'] = $this->getUsers();
        }

        return $data;
    }

    /**
     * @return array<UserResource>
     * @throws Exception
     */
    private function getUsers(): array
    {
        $users = [];

        foreach ($this->ws->getUsers() as $wu) {
            $user = new UserResource($wu);
            $users[] = $user;
        }

        return $users;
    }

    private function getInvitations(): array
    {
        $invitations = [];

        foreach ($this->ws->getInvitations() as $wi) {
            $invitation = new WorkspaceInvitationResource($wi);
            $invitations[] = $invitation;
        }

        return $invitations;
    }
}
