<?php

declare(strict_types=1);

namespace Presentation\Resources\Api;

use Ai\Domain\Entities\ImageEntity;
use Ai\Domain\Entities\MessageEntity;
use JsonSerializable;
use Override;
use Presentation\Resources\DateTimeResource;

class MessageResource implements JsonSerializable
{
    use Traits\TwigResource;

    public function __construct(
        private MessageEntity $message,
        private array $extend = []
    ) {}

    #[Override]
    public function jsonSerialize(): array
    {
        $res = $this->message;
        $items = [];

        foreach ($res->getLibraryItems() as $item) {
            match (true) {
                $item instanceof ImageEntity =>
                $items[] = new ImageResource($item),

                default => null,
            };
        }

        $data = [
            'object' => 'message',
            'id' => $res->getId(),
            'model' => $res->getModel(),
            'role' => $res->getRole(),
            'content' => $res->getContent(),
            'quote' => $res->getQuote(),
            'cost' => $res->getCost(),
            'created_at' => new DateTimeResource($res->getCreatedAt()),

            'assistant' => $res->getAssistant()
                ? new AssistantResource($res->getAssistant()) : null,

            'parent_id' => $res->getParent()
                ? $res->getParent()->getId() : null,

            'user' => $res->getUser()
                ? new UserResource($res->getUser()) : null,

            'image' => $res->getImage()
                ? new ImageFileResource($res->getImage()) : null,

            'file' => $res->getFile()
                ? new FileResource($res->getFile(), true) : null,

            'items' => $items,
            'conversation' => $res->getConversation()->getId(),
        ];

        if (in_array('conversation', $this->extend)) {
            $data['conversation'] = new ConversationResource(
                $res->getConversation()
            );
        }

        return $data;
    }
}
