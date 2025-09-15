<?php

declare(strict_types=1);

namespace Stat\Infrastructure;

use Application;
use Billing\Domain\Events\CreditUsageEvent;
use Billing\Domain\Events\OrderCreatedEvent;
use Billing\Domain\Events\SubscriptionCreatedEvent;
use Easy\EventDispatcher\Mapper\ArrayMapper;
use Override;
use Shared\Infrastructure\BootstrapperInterface;
use Stat\Domain\Repositories\StatRepositoryInterface;
use Stat\Infrastructure\Listeners\SaveStat;
use Stat\Infrastructure\Repositories\DoctrineOrm\StatRepository;
use User\Domain\Events\UserCreatedEvent;

class StatModuleBootstrapper implements BootstrapperInterface
{
    public function __construct(
        private Application $app,
        private ArrayMapper $arrayMapper,
    ) {
    }

    #[Override]
    public function bootstrap(): void
    {
        $this->app->set(
            StatRepositoryInterface::class,
            StatRepository::class
        );

        $this->arrayMapper
            ->addEventListener(
                CreditUsageEvent::class,
                SaveStat::class
            )
            ->addEventListener(
                UserCreatedEvent::class,
                SaveStat::class
            )
            ->addEventListener(
                SubscriptionCreatedEvent::class,
                SaveStat::class
            )
            ->addEventListener(
                OrderCreatedEvent::class,
                SaveStat::class
            );
    }
}
