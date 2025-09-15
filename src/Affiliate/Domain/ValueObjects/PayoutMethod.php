<?php

declare(strict_types=1);

namespace Affiliate\Domain\ValueObjects;

use JsonSerializable;
use Override;

enum PayoutMethod: string implements JsonSerializable
{
    case PAYPAL = 'paypal';
    case BANK_TRANSFER = 'bank_transfer';

    #[Override]
    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
