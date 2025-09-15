<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Currency;

use Affiliate\Domain\ValueObjects\Amount;
use Billing\Domain\ValueObjects\Price;
use Shared\Domain\ValueObjects\CurrencyCode;

interface ExchangeInterface
{
    /**
     * @template T of Price|Amount
     * @param T $amount
     * @return T
     */
    public function convert(
        Price|Amount $amount,
        CurrencyCode|string $from,
        CurrencyCode|string $to
    ): Price|Amount;
}
