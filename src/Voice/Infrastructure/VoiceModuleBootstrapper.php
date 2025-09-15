<?php

declare(strict_types=1);

namespace Voice\Infrastructure;

use Application;
use Override;
use Shared\Infrastructure\BootstrapperInterface;
use Voice\Domain\VoiceRepositoyInterface;
use Voice\Infrastructure\Repositories\DoctrineOrm\VoiceRepository;

class VoiceModuleBootstrapper implements BootstrapperInterface
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
            VoiceRepositoyInterface::class,
            VoiceRepository::class
        );
    }
}
