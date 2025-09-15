<?php

declare(strict_types=1);

namespace Affiliate\Infrastructure;

use Affiliate\Domain\Repositories\AffiliateRepositoryInterface;
use Affiliate\Domain\Repositories\PayoutRepositoryInterface;
use Affiliate\Infrastructure\Repositories\DoctrineOrm\AffiliateRepository;
use Affiliate\Infrastructure\Repositories\DoctrineOrm\PayoutRepository;
use Application;
use Override;
use Shared\Infrastructure\BootstrapperInterface;

class AffiliateModuleBootstrapper implements BootstrapperInterface
{
    public function __construct(
        private Application $app
    ) {}

    #[Override]
    public function bootstrap(): void
    {
        // Register repository implementations
        $this->app
            ->set(
                PayoutRepositoryInterface::class,
                PayoutRepository::class
            )
            ->set(
                AffiliateRepositoryInterface::class,
                AffiliateRepository::class
            );
    }
}
