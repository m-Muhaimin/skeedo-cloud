<?php

declare(strict_types=1);

namespace Voice\Domain\ValueObjects;

use JsonSerializable;
use Override;

enum Tone: string implements JsonSerializable
{
    case AUTHORITATIVE = 'authoritative';
    case PLEASANT = 'pleasant';
    case DEEP = 'deep';
    case RASPY = 'raspy';
    case WITCHY = 'witchy';
    case FOREIGNER = 'foreigner';
    case CHILDISH = 'childish';
    case ANGRY = 'angry';
    case CHEERFUL = 'cheerful';
    case SAD = 'sad';
    case EXCITED = 'excited';
    case FRIENDLY = 'friendly';
    case TERRIFIED = 'terrified';
    case SHOUTING = 'shouting';
    case UNFRIENDLY = 'unfriendly';
    case WHISPERING = 'whispering';
    case HOPEFUL = 'hopeful';
    case EMPATHETIC = 'empathetic';
    case CALM = 'calm';
    case DISGRUNTLED = 'disgruntled';
    case FEARFUL = 'fearful';
    case GENTLE = 'gentle';
    case SERIOUS = 'serious';
    case SORRY = 'sorry';
    case EMBARRASSED = 'embarrassed';
    case DEPRESSED = 'depressed';
    case ENVIOUS = 'envious';
    case LYRICAL = 'lyrical';
    case CASUAL = 'casual';

    case WELL_ROUNDED = 'well-rounded';
    case WAR_VETERAN = 'war-veteran';
    case GROUND_REPORTER = 'ground-reporter';
    case STRONG = 'strong';
    case SAILOR = 'sailor';
    case SOFT = 'soft';
    case WARM = 'warm';
    case EMOTIONAL = 'emotional';
    case INTENSE = 'intense';
    case SHOUTY = 'shouty';
    case ANXIOUS = 'anxious';
    case CRISP = 'crisp';
    case SEDUCTIVE = 'seductive';
    case CONFIDENT = 'confident';
    case OROTUND = 'orotund';
    case OVERHYPED = 'overhyped';
    case MATURE = 'mature';

    case DIRECT = 'direct';
    case PROFESSIONAL = 'professional';
    case NEUTRAL = 'neutral';
    case ENERGETIC = 'energetic';
    case HIGH_PITCH = 'high-pitch';
    case RELAXED = 'relaxed';

    #[Override]
    public function jsonSerialize(): string
    {
        return $this->value;
    }

    public static function create(string $val): ?static
    {
        $value = trim(strtolower($val));
        $value = str_replace([' ', '_'], '-', $value);

        $map = [
            'whisper' => 'whispering',
            'witch' => 'witchy',
            'assertive-or-confident' => 'confident',
            'warm-or-friendly' => 'warm',
            'calm-or-relaxed' => 'calm',
        ];

        $value = $map[$value] ?? $value;
        return self::tryFrom($value);
    }
}
