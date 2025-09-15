<?php

declare(strict_types=1);

namespace User\Domain\ValueObjects;

use JsonSerializable;
use Override;

/**
 * Class Preferences
 *
 * Represents the set of user preferences
 *
 * Implements JsonSerializable for easy conversion to JSON.
 */
class Preferences implements JsonSerializable
{
    /**
     * Whether the "Saved memories" feature is enabled for the user.
     */
    public readonly bool $memories;

    /**
     * Whether the "Chat history" feature is enabled for the user.
     */
    public readonly bool $history;

    /**
     * Preferences constructor.
     *
     * @param array{
     *     memories?: bool,
     *     history?: bool
     * }|null $data Optional associative array of user preferences.
     */
    public function __construct(?array $data = null)
    {
        $data = $data ?? [];

        $this->memories = (bool) ($data['memories'] ?? true);
        $this->history = (bool) ($data['history'] ?? true);
    }

    /**
     * Specify data which should be serialized to JSON.
     *
     * @return array{
     *     memories: bool,
     *     history: bool
     * }
     */
    #[Override]
    public function jsonSerialize(): array
    {
        return [
            'memories' => $this->memories,
            'history' => $this->history,
        ];
    }

    public function mergeWith(Preferences $preferences): static
    {
        return new static(array_merge(
            $this->jsonSerialize(),
            $preferences->jsonSerialize()
        ));
    }
}
