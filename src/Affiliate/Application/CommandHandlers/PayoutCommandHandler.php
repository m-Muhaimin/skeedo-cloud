<?php

declare(strict_types=1);

namespace Affiliate\Application\CommandHandlers;

use Affiliate\Application\Commands\PayoutCommand;
use Affiliate\Domain\Entities\PayoutEntity;
use Affiliate\Domain\Exceptions\InsufficientBalanceException;
use Billing\Infrastructure\Currency\ExchangeInterface;
use Easy\Container\Attributes\Inject;

class PayoutCommandHandler
{
    public function __construct(
        private ExchangeInterface $exchange,

        #[Inject('option.affiliate.min_payout')]
        private int $minPayoutAmount = 0,

        #[Inject('option.billing.currency')]
        private string $currency = 'USD',
    ) {}

    public function handle(PayoutCommand $cmd): PayoutEntity
    {
        $affiliate = $cmd->user->getAffiliate();

        $balance = $this->exchange->convert(
            $affiliate->getBalance(),
            'USD',
            $this->currency,
        );

        if ($balance->value < $this->minPayoutAmount) {
            throw new InsufficientBalanceException();
        }

        return $affiliate->payout();
    }
}
