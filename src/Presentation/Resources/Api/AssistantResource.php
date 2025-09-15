<?php

declare(strict_types=1);

namespace Presentation\Resources\Api;

use Assistant\Domain\Entities\AssistantEntity;
use Billing\Domain\Entities\SubscriptionEntity;
use JsonSerializable;
use Override;
use Presentation\Resources\DateTimeResource;

class AssistantResource implements JsonSerializable
{
    use Traits\TwigResource;

    public function __construct(
        private AssistantEntity $assistant,
        private ?SubscriptionEntity $subscription = null
    ) {}

    #[Override]
    public function jsonSerialize(): array
    {
        $res = $this->assistant;

        $data = [
            'object' => 'assistant',
            'id' => $res->getId(),
            'name' => $res->getName(),
            'expertise' => $res->getExpertise(),
            'description' => $res->getDescription(),
            'avatar' => $res->getAvatar(),
            'model' => $res->getModel(),
            'created_at' => new DateTimeResource($res->getCreatedAt()),
            'updated_at' => new DateTimeResource($res->getUpdatedAt()),
            'granted' => false,
        ];

        if ($this->subscription) {
            $config = $this->subscription->getPlan()->getConfig();
            $data['granted'] = is_null($config->assistants) || in_array(
                (string)$this->assistant->getId(),
                $config->assistants
            );
        }

        return $data;
    }
}
