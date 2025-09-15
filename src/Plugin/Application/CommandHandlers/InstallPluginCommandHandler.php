<?php

declare(strict_types=1);

namespace Plugin\Application\CommandHandlers;

use Easy\Container\Attributes\Inject;
use Plugin\Application\Commands\InstallPluginCommand;
use Plugin\Domain\Context;
use Plugin\Domain\ValueObjects\Status;
use Plugin\Infrastructure\Helpers\ComposerHelper;
use RuntimeException;
use Shared\Infrastructure\CacheManager;
use Symfony\Component\Console\Output\BufferedOutput;

class InstallPluginCommandHandler
{
    public function __construct(
        private ComposerHelper $helper,
        private CacheManager $cache,

        #[Inject('config.dirs.extensions')]
        private string $extDir
    ) {}

    public function handle(InstallPluginCommand $cmd): void
    {
        $output = new BufferedOutput();
        $code = $this->helper->require($cmd->name->value, $output);

        if ($code !== 0) {
            throw new RuntimeException(
                "Failed to install plugin with following code: " . $output->fetch()
            );
        }

        // Set status to inactive
        $context = new Context($this->extDir . '/' . $cmd->name->value);
        $context->setStatus(Status::INACTIVE);

        // Clear cache
        $this->cache->clearCache();
    }
}
