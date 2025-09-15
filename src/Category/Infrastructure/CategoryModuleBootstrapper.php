<?php

declare(strict_types=1);

namespace Category\Infrastructure;

use Application;
use Category\Domain\Repositories\CategoryRepositoryInterface;
use Category\Infrastructure\Repositories\DoctrineOrm\CategoryRepository;
use Override;
use Shared\Infrastructure\BootstrapperInterface;

class CategoryModuleBootstrapper implements BootstrapperInterface
{
    public function __construct(
        private Application $app
    ) {
    }

    #[Override]
    public function bootstrap(): void
    {
        // Register repository implementations
        $this->app->set(
            CategoryRepositoryInterface::class,
            CategoryRepository::class
        );
    }
}
