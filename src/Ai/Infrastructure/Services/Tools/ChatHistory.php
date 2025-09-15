<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services\Tools;

use Ai\Domain\Entities\ConversationEntity;
use Ai\Domain\Repositories\LibraryItemRepositoryInterface;
use Ai\Domain\ValueObjects\ItemType;
use Ai\Domain\ValueObjects\SortParameter;
use Billing\Domain\ValueObjects\CreditCount;
use Easy\Container\Attributes\Inject;
use Override;
use Shared\Domain\ValueObjects\MaxResults;
use Shared\Domain\ValueObjects\SortDirection;
use User\Domain\Entities\UserEntity;
use Workspace\Domain\Entities\WorkspaceEntity;

class ChatHistory implements ToolInterface
{
    public const LOOKUP_KEY = 'chat_history';

    public function __construct(
        private LibraryItemRepositoryInterface $repo,

        #[Inject('option.features.tools.memory.is_enabled')]
        private ?bool $isEnabled = null,
    ) {}

    #[Override]
    public function isEnabled(): bool
    {
        return (bool) $this->isEnabled;
    }

    #[Override]
    public function getDescription(): string
    {
        return 'Retrieves relevant messages from recent chats accessible to the user within the workspace for context-aware responses.';
    }

    #[Override]
    public function getDefinitions(): array
    {
        return [
            "type" => "object",
            "properties" => [
                "limit" => [
                    "type" => "integer",
                    "description" => "The number of memories to retrieve. Minimum 1, maximum 10."
                ]
            ]
        ];
    }

    /**
     * Intelligently truncates text to complete sentences within character limits
     */
    private function truncateToCompleteSentence(
        string $text,
        int $targetLength = 100,
        int $maxLength = 500
    ): string {
        // If text is already within target length, return as is
        if (mb_strlen($text) <= $targetLength) {
            return $text;
        }

        // If text is longer than max length, truncate to max length first
        if (mb_strlen($text) > $maxLength) {
            $text = mb_substr($text, 0, $maxLength);
        }

        // Find sentence endings (., !, ?, :, ;) followed by whitespace or end of string
        $sentenceEndings = ['.', '!', '?', ':', ';'];
        $bestCutoff = $targetLength;
        $bestLength = 0;
        $closestToTarget = $targetLength;
        $closestDistance = PHP_INT_MAX;

        // Look for sentence endings within the entire range (0 to maxLength)
        for ($i = 1; $i <= mb_strlen($text); $i++) {
            $char = mb_substr($text, $i - 1, 1);

            if (in_array($char, $sentenceEndings)) {
                // Check if next character is whitespace or end of string
                $nextChar = mb_substr($text, $i, 1);
                if ($i >= mb_strlen($text) || preg_match('/\s/', $nextChar)) {
                    $distance = abs($i - $targetLength);

                    // If this sentence ending is within the target range, prefer it
                    if ($i >= $targetLength && $i <= $maxLength) {
                        $bestCutoff = $i;
                        $bestLength = $i;
                        break; // Found a good one, stop searching
                    }

                    // Keep track of the closest sentence ending to target
                    if ($distance < $closestDistance) {
                        $closestDistance = $distance;
                        $closestToTarget = $i;
                    }
                }
            }
        }

        // If we found a sentence ending within the target range, use it
        if ($bestLength > 0) {
            return mb_substr($text, 0, $bestCutoff);
        }

        // If no sentence ending in target range, use the closest one if it's reasonable
        if ($closestToTarget > 0 && $closestToTarget <= $maxLength) {
            return mb_substr($text, 0, $closestToTarget);
        }

        // If still no good sentence ending found, try to find word boundaries
        for ($i = $targetLength; $i <= mb_strlen($text); $i++) {
            $char = mb_substr($text, $i - 1, 1);
            if (preg_match('/\s/', $char)) {
                return mb_substr($text, 0, $i - 1); // Cut before the space
            }
        }

        // Last resort: use the target length
        return mb_substr($text, 0, $targetLength);
    }

    #[Override]
    public function call(
        UserEntity $user,
        WorkspaceEntity $workspace,
        array $params = [],
        array $files = [],
        array $knowledgeBase = [],
    ): CallResponse {
        $entities = $this->repo->filterByUser($user, $workspace)
            ->filterByType(ItemType::CONVERSATION)
            ->sort(SortDirection::DESC, SortParameter::UPDATED_AT)
            ->setMaxResults(new MaxResults(10));

        $conversations = [];
        /** @var ConversationEntity */
        foreach ($entities as $entity) {
            $messages = [];

            foreach ($entity->getMessages(-10) as $m) {
                $message = [
                    'id' => $m->getId()->getValue()->toString(),
                    'role' => $m->getRole()->name,
                    'content' => $this->truncateToCompleteSentence($m->getContent()->value, 100, 500),
                    'created_at' => $m->getCreatedAt()->getTimestamp(),
                    'owner' => null,
                ];

                $user = $m->getUser();
                if ($user) {
                    $message['owner'] = [
                        'id' => $user->getId()->getValue()->toString(),
                        'first_name' => $user->getFirstName(),
                        'last_name' => $user->getLastName(),
                        'email' => $user->getEmail(),
                    ];
                }

                $messages[] = $message;
            }

            $conversations[] = [
                'id' => $entity->getId()->getValue()->toString(),
                'title' => $entity->getTitle()->value,
                'messages' => $messages,
                'created_at' => $entity->getUpdatedAt()->getTimestamp(),
                'owner' => [
                    'id' => $entity->getUser()->getId()->getValue()->toString(),
                    'first_name' => $entity->getUser()->getFirstName(),
                    'last_name' => $entity->getUser()->getLastName(),
                    'email' => $entity->getUser()->getEmail(),
                ],
            ];
        }

        $content = "Workspace recent chats are listed below. Pick the most relevant chat to answer the user's question. <chat_history>: " . json_encode($conversations, JSON_PRETTY_PRINT) . "</chat_history>";

        return new CallResponse(
            $content,
            new CreditCount(0)
        );
    }
}
