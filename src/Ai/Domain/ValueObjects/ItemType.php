<?php

declare(strict_types=1);

namespace Ai\Domain\ValueObjects;

use JsonSerializable;
use Override;

enum ItemType: string implements JsonSerializable
{
    case IMAGE = 'image';
    case VIDEO = 'video';
    case DOCUMENT = 'document';
    case CODE_DOCUMENT = 'code_document';
    case TRANSCRIPTION = 'transcription';
    case SPEECH = 'speech';
    case CONVERSATION = 'conversation';
    case ISOLATED_VOICE = 'isolated_voice';
    case CLASSIFICATION = 'classification';
    case COMPOSITION = 'composition';
    case MEMORY = 'memory';

    #[Override]
    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
