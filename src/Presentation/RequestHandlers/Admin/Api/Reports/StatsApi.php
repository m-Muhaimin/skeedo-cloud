<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin\Api\Reports;

use Affiliate\Application\Commands\ReadStatsCommand;
use Billing\Application\Commands\CountOrdersCommand;
use Billing\Domain\ValueObjects\OrderStatus;
use Billing\Domain\ValueObjects\Price;
use Billing\Infrastructure\Currency\ExchangeInterface;
use DateTime;
use Easy\Container\Attributes\Inject;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Presentation\Resources\CurrencyResource;
use Presentation\Resources\DateTimeResource;
use Presentation\Response\JsonResponse;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Stat\Application\Commands\ReadStatCommand;
use Stat\Domain\ValueObjects\StatType;
use User\Application\Commands\CountUsersCommand;
use User\Domain\ValueObjects\Status;

#[Route(path: '/stats', method: RequestMethod::GET)]
class StatsApi extends ReportsApi implements RequestHandlerInterface
{
    private const CACHE_VERSION = 1;
    private const CACHE_TTL = 60; // 1 minute

    public function __construct(
        private Dispatcher $dispatcher,
        private CacheItemPoolInterface $cache,
        private ExchangeInterface $exchange,

        #[Inject('config.enable_caching')]
        private bool $enableCaching = false,

        #[Inject('option.billing.currency')]
        private string $currency = 'USD',

        #[Inject('option.mrr')]
        private ?array $mrr = null,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $stats = $this->getStats();
        return new JsonResponse($stats);
    }

    private function getStats(): array
    {
        $key = sprintf('stats.v%d', self::CACHE_VERSION);
        $item = $this->cache->getItem($key);

        if ($this->enableCaching && $item->isHit()) {
            return $item->get();
        }

        $stats = $this->getLiveStats();

        if ($this->enableCaching) {
            $item->set($stats);
            $item->expiresAfter(self::CACHE_TTL);
            $this->cache->save($item);
        }

        return $stats;
    }

    private function getLiveStats(): array
    {
        $stats = [];

        foreach (StatType::cases() as $type) {
            $cmd = new ReadStatCommand($type);
            $cmd->day = new DateTime();

            $last = $this->dispatcher->dispatch($cmd);

            $cmd->day = (new DateTime())->modify('-1 day');
            $prev = $this->dispatcher->dispatch($cmd);

            if ($prev == $last) {
                $changePer = 0;
            } else {
                $changePer = $prev === 0 ? 100 : ($last === 0 ? -100 : (($last - $prev) / $prev) * 100);
            }

            $stats[$type->value] = [
                'metric' => $last,
                'change' => round($changePer, 2),
            ];
        }

        // Online users
        $cmd = new CountUsersCommand();
        $cmd->status = Status::ONLINE;
        $online = $this->dispatcher->dispatch($cmd);

        $stats['online'] = [
            'metric' => $online,
            'change' => null,
        ];

        // Pending orders count
        $cmd = new CountOrdersCommand();
        $cmd->status = OrderStatus::PENDING;
        $pending = $this->dispatcher->dispatch($cmd);

        $stats['orders'] = [
            'pending' => [
                'metric' => $pending,
                'change' => null,
            ],
        ];

        // Affiliates
        $cmd = new ReadStatsCommand($this->currency);

        /** @var array{
         *  clicks: int,
         *  referrals: int,
         *  balance: float,
         *  pending: float,
         *  withdrawn: float,
         *  earnings: float,
         *  total: float,
         * } $affiliates */
        $affiliates = $this->dispatcher->dispatch($cmd);

        $keys = ['clicks', 'referrals', 'balance', 'pending', 'withdrawn', 'earnings', 'total'];
        $stats['affiliates'] = [];
        foreach ($keys as $key) {
            $stats['affiliates'][$key] = [
                'metric' => $affiliates[$key] ?? 0,
                'change' => null,
            ];
        }

        // MRR
        $currency = $this->currency;
        $stats['mrr'] = [
            'metric' => isset($this->mrr['value']) ? $this->exchange->convert(
                new Price((int)$this->mrr['value']),
                'USD',
                $currency
            )->value : null,
            'calculated_at' => $this->mrr['calculated_at'] ?? null,
            'change' => null,
        ];

        return $stats;
    }
}
