<?php

declare(strict_types=1);

namespace Plugin\Domain\ValueObjects;

use JsonSerializable;

enum SupportChannelType: string implements JsonSerializable
{
    case CHAT = 'chat';
    case DOCS = 'docs';
    case EMAIL = 'email';
    case FORUM = 'forum';
    case IRC = 'irc';
    case ISSUES = 'issues';
    case RSS = 'rss';
    case SOURCE = 'source';
    case WIKI = 'wiki';

    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
