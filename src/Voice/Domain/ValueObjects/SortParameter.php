<?php

declare(strict_types=1);

namespace Voice\Domain\ValueObjects;

enum SortParameter: string
{
    case ID = 'id';
    case CREATED_AT = 'created_at';
    case UPDATED_AT = 'updated_at';
    case NAME = 'name';
    case POSITION = 'position';
}
