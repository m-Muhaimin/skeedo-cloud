<?php

declare(strict_types=1);

namespace Affiliate\Application\Commands;

use Affiliate\Application\CommandHandlers\ReadStatsCommandHandler;
use Shared\Domain\ValueObjects\CurrencyCode;
use Shared\Infrastructure\CommandBus\Attributes\Handler;

#[Handler(ReadStatsCommandHandler::class)]
class ReadStatsCommand
{
    public CurrencyCode $currency;

    public function __construct(
        string|CurrencyCode $currency = 'USD',
    ) {
        $this->currency = $currency instanceof CurrencyCode
            ? $currency : CurrencyCode::from($currency);
    }
}
