<?php

declare(strict_types=1);

namespace Voice\Domain\ValueObjects;

use JsonSerializable;
use Override;

enum UseCase: string implements JsonSerializable
{
    case GENERAL = 'general';
    case NARRATION = 'narration';
    case NEWS = 'news';
    case VIDEO_GAMES = 'video-games';
    case MEDITATION = 'meditation';
    case CONVERSATIONAL = 'conversational';
    case CHARACTERS = 'characters';
    case CHILDREN_STORIES = 'children-stories';

    case NEWSCAST = 'newscast';
    case CHAT = 'chat';
    case CUSTOMERSERVICE = 'customer-service';
    case NARRATION_PROFESSIONAL = 'narration-professional';
    case NEWSCAST_CASUAL = 'newscast-casual';
    case NEWSCAST_FORMAL = 'newscast-formal';
    case ASSISTANT = 'assistant';
    case POETRY_READING = 'poetry-reading';
    case NARRATION_RELAXED = 'narration-relaxed';
    case SPORTS_COMMENTARY = 'sports-commentary';
    case SPORTS_COMMENTARY_EXCITED = 'sports-commentary-excited';
    case DOCUMENTARY_NARRATION = 'documentary-narration';
    case LIVECOMMERCIAL = 'live-commercial';
    case ADVERTISEMENT_UPBEAT = 'advertisement-upbeat';
    case CHAT_CASUAL = 'chat-casual';

    case AUDIOBOOK = 'audiobook';
    case ASMR = 'asmr';
    case ANIMATION = 'animation';
    case INTERACTIVE = 'interactive';
    case INFORMATIVE_EDUCATIONAL = 'informative-educational';

    case SOCIAL_MEDIA = 'social-media';
    case E_LEARNING = 'e-learning';
    case MOVIES = 'movies';

    #[Override]
    public function jsonSerialize(): string
    {
        return $this->value;
    }

    public static function create(string $val): ?static
    {
        $value = trim(strtolower($val));
        $map = [
            'children\'s stories' => 'children-stories',
            'customerservice' => 'customer-service',
            'livecommercial' => 'live-commercial',
            'advertisement' => 'advertisement-upbeat',
            'movies-acting' => 'movies',
            'gaming' => 'video-games',
        ];

        $value = $map[$value] ?? $value;

        $value = str_replace([' ', '_'], '-', $value);
        return self::tryFrom($value);
    }
}
