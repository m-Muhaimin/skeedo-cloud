<?php

declare(strict_types=1);

namespace Stat\Domain\ValueObjects;

use JsonSerializable;
use Override;

enum DatasetCategory: string implements JsonSerializable
{
    case DATE = 'date';
    case COUNTRY = 'country';
    case WORKSPACE_USAGE = 'workspace_usage'; // Usage by workspace

    #[Override]
    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
