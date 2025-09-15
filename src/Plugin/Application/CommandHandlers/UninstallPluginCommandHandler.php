<?php

declare(strict_types=1);

namespace Plugin\Application\CommandHandlers;

use Composer\Console\Application;
use Easy\Container\Attributes\Inject;
use Plugin\Application\Commands\UninstallPluginCommand;
use Plugin\Domain\Hooks\UninstallHookInterface;
use Plugin\Domain\Repositories\PluginRepositoryInterface;
use Plugin\Domain\ValueObjects\Type;
use Shared\Infrastructure\CacheManager;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class UninstallPluginCommandHandler
{
    public function __construct(
        private PluginRepositoryInterface $repo,
        private Application $composer,
        private CacheManager $cache,

        #[Inject('config.dirs.extensions')]
        private string $extDir,

        #[Inject('option.theme')]
        private string $theme = 'heyaikeedo/default',
    ) {
        $this->composer->setAutoExit(false);
    }

    public function handle(UninstallPluginCommand $cmd): void
    {
        $pw = $this->repo->ofName($cmd->name);
        $ins = $pw->plugin;
        $context = $pw->context;

        if (
            $context->type == Type::THEME
            && $context->name->value == $this->theme
        ) {
            throw new \RuntimeException("Cannot uninstall default theme");
        }

        if ($ins instanceof UninstallHookInterface) {
            $ins->uninstall($pw->context);
        }

        $context = $pw->context;

        $output = new BufferedOutput();

        $input = new ArrayInput([
            'command' => 'remove',
            'packages' => [$context->name->value]
        ]);

        $code = $this->composer->run($input, $output);

        if ($code !== 0) {
            throw new \RuntimeException(
                "Failed to remove plugin/theme with following code: " . $code
            );
        }

        // Remove plugin from filesystem
        $this->rrmdir($this->extDir . '/' . $context->name->value);

        $this->repo->remove($pw);

        // Clear cache
        $this->cache->clearCache();
    }

    private function rrmdir($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . DIRECTORY_SEPARATOR . $object) && !is_link($dir . "/" . $object))
                        $this->rrmdir($dir . DIRECTORY_SEPARATOR . $object);
                    else
                        unlink($dir . DIRECTORY_SEPARATOR . $object);
                }
            }

            rmdir($dir);
        }
    }
}
