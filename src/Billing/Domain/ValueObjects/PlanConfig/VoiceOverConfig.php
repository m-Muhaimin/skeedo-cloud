<?php

declare(strict_types=1);

namespace Billing\Domain\ValueObjects\PlanConfig;

use JsonSerializable;
use Override;

class VoiceOverConfig implements JsonSerializable
{
    /**
     * @param bool $isEnabled Whether or not voice over is enabled
     * @param null|int $cap The maximum number of cloned voices
     * @return void
     */
    public function __construct(
        public readonly bool $isEnabled,
        public readonly ?int $cap = null,
    ) {
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return [
            'is_enabled' => $this->isEnabled,
            'clone_cap' => $this->cap,
        ];
    }
}

