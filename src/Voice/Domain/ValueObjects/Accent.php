<?php

declare(strict_types=1);

namespace Voice\Domain\ValueObjects;

use JsonSerializable;

enum Accent: string implements JsonSerializable
{
    case AMERICAN = 'american';
    case AMERICAN_IRISH = 'american-irish';
    case AMERICAN_SOUTHERN = 'american-southern';
    case AUSTRALIAN = 'australian';
    case BRITISH = 'british';
    case BRITISH_ESSEX = 'british-essex';
    case ENGLISH_SWEDISH = 'english-swedish';
    case ENGLISH_ITALIAN = 'english-italian';
    case INDIAN = 'indian';
    case IRISH = 'irish';

    public function jsonSerialize(): string
    {
        return $this->value;
    }

    public static function create(string $val): ?static
    {
        $value = strtolower(trim($val));
        $map = [
            'american-neutral' => 'american'
        ];

        $value = $map[$value] ?? $value;
        return self::tryFrom($value);
    }
}
