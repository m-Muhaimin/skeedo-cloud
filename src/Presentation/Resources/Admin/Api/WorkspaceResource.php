<?php

declare(strict_types=1);

namespace Presentation\Resources\Admin\Api;

use Exception;
use JsonSerializable;
use Presentation\Resources\Api\Traits\TwigResource;
use Presentation\Resources\DateTimeResource;
use Workspace\Domain\Entities\WorkspaceEntity;

class WorkspaceResource implements JsonSerializable
{
    use TwigResource;

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
            'address' => $ws->getAddress(),
            'is_eligible_for_trial' => $ws->isEligibleForTrial(),
            'created_at' => new DateTimeResource($ws->getCreatedAt()),
            'updated_at' => new DateTimeResource($ws->getUpdatedAt()),
            'invitations' => $this->getInvitations(),
            'credit_count' => $ws->getCreditCount(),
            'credits_adjusted_at' => new DateTimeResource($ws->getCreditsAdjustedAt()),
            'total_credit_count' => $ws->getTotalCreditCount(),
            'subscription' => $sub ? new SubscriptionResource($sub) : null,
        ];

        if (in_array('user', $this->extend)) {
            $data['owner'] = new UserResource($ws->getOwner());
            $data['users'] = $this->getUsers();
        }

        if (in_array('owner', $this->extend)) {
            $data['owner'] = new UserResource($ws->getOwner());
        }

        $extend = array_map(
            fn($v) => str_replace('subscription.', '', $v),

            array_filter(
                $this->extend,
                fn($v) => str_starts_with($v, 'subscription.')
            )
        );

        $data['subscription'] = $sub ? new SubscriptionResource($sub, $extend) : null;

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
