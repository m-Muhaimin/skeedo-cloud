<?php

declare(strict_types=1);

namespace File\Infrastructure;

use Application;
use File\Domain\Repositories\FileRepositoryInterface;
use File\Infrastructure\Repositories\DoctrineOrm\FileRepository;
use Override;
use Shared\Infrastructure\BootstrapperInterface;

class FileModuleBootstrapper implements BootstrapperInterface
{
    public function __construct(
        private Application $app
    ) {}

    #[Override]
    public function bootstrap(): void
    {
        // Register repository implementations
        $this->app->set(
            FileRepositoryInterface::class,
            FileRepository::class
        );
    }
}
