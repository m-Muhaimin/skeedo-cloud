<?php

declare(strict_types=1);

namespace Ai\Domain\ValueObjects;

use JsonSerializable;
use Override;

enum MessageRole: string implements JsonSerializable
{
    case SYSTEM = 'system';
    case USER = 'user';
    case ASSISTANT = 'assistant';

    #[Override]
    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
