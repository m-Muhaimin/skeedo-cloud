<?php

declare(strict_types=1);

namespace Option\Infrastructure;

use Application;
use Easy\Container\Container;
use Option\Domain\Repositories\OptionRepositoryInterface;
use Option\Infrastructure\Repositories\DoctrineOrm\OptionRepository;
use Override;
use Shared\Infrastructure\BootstrapperInterface;

class OptionModuleBootstrapper implements BootstrapperInterface
{
    public function __construct(
        private Application $app,
        private Container $container,
        private OptionResolver $resolver
    ) {
    }

    #[Override]
    public function bootstrap(): void
    {
        // Register resolver
        $this->container->pushResolver($this->resolver);

        // Register repository implementations
        $this->app->set(
            OptionRepositoryInterface::class,
            OptionRepository::class
        );
    }
}
