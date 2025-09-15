<?php

declare(strict_types=1);

namespace Voice\Domain\ValueObjects;

use JsonSerializable;

enum Age: string implements JsonSerializable
{
    case YOUNG = 'young';
    case MIDDLE_AGED = 'middle-aged';
    case OLD = 'old';

    public function jsonSerialize(): string
    {
        return $this->value;
    }

    public static function create(string $val): ?static
    {
        $value = strtolower(trim($val));
        $map = [
            'young-adult' => 'young'
        ];

        $value = $map[$value] ?? $value;
        return self::tryFrom($value);
    }
}
