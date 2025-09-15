<?php

declare(strict_types=1);

namespace Affiliate\Domain\ValueObjects;

use JsonSerializable;
use Override;

enum Status: string implements JsonSerializable
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    #[Override]
    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
