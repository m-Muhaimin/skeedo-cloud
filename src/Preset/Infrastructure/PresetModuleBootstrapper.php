<?php

declare(strict_types=1);

namespace Preset\Infrastructure;

use Application;
use Override;
use Preset\Domain\Repositories\PresetRepositoryInterface;
use Preset\Infrastructure\DoctrineOrm\PresetRepository;
use Shared\Infrastructure\BootstrapperInterface;

class PresetModuleBootstrapper implements BootstrapperInterface
{
    public function __construct(private Application $app)
    {
    }

    #[Override]
    public function bootstrap(): void
    {
        // Register repository implementations
        $this->app->set(
            PresetRepositoryInterface::class,
            PresetRepository::class
        );
    }
}
