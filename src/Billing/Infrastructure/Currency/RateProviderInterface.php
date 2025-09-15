<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Currency;

use Shared\Domain\ValueObjects\CurrencyCode;

interface RateProviderInterface
{
    /**
     * Get the name of the rate provider service
     *
     * @return string The name of the rate provider service
     */
    public function getName(): string;

    /**
     * Get the rate of the given currency pair
     *
     * @param CurrencyCode $from The currency to convert from
     * @param CurrencyCode $to The currency to convert to
     * @return int|float The rate of the given currency pair
     */
    public function getRate(CurrencyCode $from, CurrencyCode $to): int|float;
}
