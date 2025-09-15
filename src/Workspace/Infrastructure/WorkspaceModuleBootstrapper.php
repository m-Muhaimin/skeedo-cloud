<?php

declare(strict_types=1);

namespace Workspace\Infrastructure;

use Application;
use Override;
use Shared\Infrastructure\BootstrapperInterface;
use Workspace\Domain\Repositories\WorkspaceRepositoryInterface;
use Workspace\Infrastructure\Repositories\DoctrineOrm\WorkspaceRepository;

class WorkspaceModuleBootstrapper implements BootstrapperInterface
{
    public function __construct(
        private Application $app,
    ) {
    }

    #[Override]
    public function bootstrap(): void
    {
        // Register repository implementations
        $this->app->set(
            WorkspaceRepositoryInterface::class,
            WorkspaceRepository::class
        );
    }
}
