<?php

declare(strict_types=1);

namespace Affiliate\Application\CommandHandlers;

use Affiliate\Application\Commands\ReadStatsCommand;
use Affiliate\Domain\Repositories\AffiliateRepositoryInterface;
use Affiliate\Domain\ValueObjects\Amount;
use Billing\Infrastructure\Currency\ExchangeInterface;

class ReadStatsCommandHandler
{
    public function __construct(
        private AffiliateRepositoryInterface $repo,
        private ExchangeInterface $exchange
    ) {}

    public function handle(ReadStatsCommand $command): array
    {
        $metrics = [
            'clicks' => $this->repo->getTotalClicks(),
            'referrals' => $this->repo->getTotalReferrals(),
            'balance' => $this->repo->getTotalBalance(),
            'pending' => $this->repo->getTotalPending(),
            'withdrawn' => $this->repo->getTotalWithdrawn(),
        ];

        $monetary = ['balance', 'pending', 'withdrawn'];
        foreach ($monetary as $key) {
            $metrics[$key] = $this->exchange->convert(
                new Amount($metrics[$key]),
                'USD',
                $command->currency
            )->value;
        }

        $metrics['earnings'] = $metrics['balance'] + $metrics['pending'];
        $metrics['total'] = $metrics['earnings'] + $metrics['withdrawn'];

        return $metrics;
    }
}
