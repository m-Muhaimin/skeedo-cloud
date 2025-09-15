<?php

declare(strict_types=1);

namespace Presentation\Resources\Api;

use Ai\Domain\Entities\ConversationEntity;
use JsonSerializable;
use Override;
use Presentation\Resources\DateTimeResource;

class ConversationResource implements JsonSerializable
{
    use Traits\TwigResource;

    public function __construct(
        private ConversationEntity $conversation,
        private array $extend = []
    ) {
    }

    #[Override]
    public function jsonSerialize(): array
    {
        $conv = $this->conversation;

        $data = [
            'object' => 'conversation',
            'id' => $conv->getId(),
            'visibility' => $conv->getVisibility(),
            'cost' => $conv->getCost(),
            'created_at' => new DateTimeResource($conv->getCreatedAt()),
            'updated_at' => new DateTimeResource($conv->getUpdatedAt()),
            'title' => $conv->getTitle(),
            'user' => new UserResource($conv->getUser()),
        ];

        if (in_array('messages', $this->extend)) {
            $messages = [];
            foreach ($conv->getMessages() as $msg) {
                $messages[] = new MessageResource($msg);
            }

            $data['messages'] = $messages;
        }

        return $data;
    }
}
