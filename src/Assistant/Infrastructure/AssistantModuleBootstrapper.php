<?php

declare(strict_types=1);

namespace Assistant\Infrastructure;

use Application;
use Assistant\Domain\Repositories\AssistantRepositoryInterface;
use Assistant\Infrastructure\Repositories\DoctrineOrm\AssistantRepository;
use Override;
use Shared\Infrastructure\BootstrapperInterface;

class AssistantModuleBootstrapper implements BootstrapperInterface
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
            AssistantRepositoryInterface::class,
            AssistantRepository::class
        );
    }
}
