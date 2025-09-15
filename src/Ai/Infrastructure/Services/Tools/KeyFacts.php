<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services\Tools;

use Billing\Domain\ValueObjects\CreditCount;
use Override;
use User\Domain\Entities\UserEntity;
use Workspace\Domain\Entities\WorkspaceEntity;

class KeyFacts implements ToolInterface
{
    public const LOOKUP_KEY = 'key_facts';

    #[Override]
    public function isEnabled(): bool
    {
        // This tool is always enabled
        return true;
    }

    #[Override]
    public function getDescription(): string
    {
        return "Retrieves key facts about the user, workspace, and subscription including names, emails, and other available data.";
    }

    #[Override]
    public function getDefinitions(): array
    {
        return [
            "type" => "object",
            "properties" => [
                "query" => [
                    "type" => "string",
                    "description" => "The query to retrieve facts about."
                ]
            ],
            "required" => []
        ];
    }

    #[Override]
    public function call(
        UserEntity $user,
        WorkspaceEntity $workspace,
        array $params = [],
        array $files = [],
        array $knowledgeBase = [],
    ): CallResponse {
        $data = [
            'user' => [
                'id' => $user->getId()->getValue()->toString(),
                'first_name' => $user->getFirstName(),
                'last_name' => $user->getLastName(),
                'email' => $user->getEmail(),
            ],
            'workspace' => [
                'id' => $workspace->getId()->getValue()->toString(),
                'name' => $workspace->getName(),
                'subscription' => null,
                'owner' => [
                    'id' => $workspace->getOwner()->getId()->getValue()->toString(),
                    'first_name' => $workspace->getOwner()->getFirstName(),
                    'last_name' => $workspace->getOwner()->getLastName(),
                    'email' => $workspace->getOwner()->getEmail(),
                ],
            ]
        ];

        $sub = $workspace->getSubscription();
        if ($sub) {
            $plan = $sub->getPlan();
            $data['workspace']['subscription'] = [
                'id' => $sub->getId()->getValue()->toString(),
                'created_at' => $sub->getCreatedAt()->getTimestamp(),
                'plan' => [
                    'name' => $plan->getTitle(),
                    'billing_cycle' => $plan->getBillingCycle()->name,
                ],
            ];
        }

        $content = "Key facts about the user, workspace, and subscription are listed below. <facts>: " . json_encode($data, JSON_PRETTY_PRINT) . "</facts>";

        return new CallResponse(
            $content,
            new CreditCount(0)
        );
    }
}
