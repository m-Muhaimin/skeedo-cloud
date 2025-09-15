<?php

declare(strict_types=1);

namespace Plugin\Infrastructure\Helpers;

use Composer\Console\Application;
use Composer\Util\Platform;
use Easy\Container\Attributes\Inject;
use Exception;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ComposerHelper
{
    public function __construct(
        private Application $composer,

        #[Inject('config.dirs.webroot')]
        private string $webroot = 'public'
    ) {
        $this->composer->setAutoExit(false);

        // Transfer PUBLIC_DIR environment variable to Composer
        Platform::putEnv('PUBLIC_DIR', $this->webroot);
    }

    /**
     * @param string|string[] $packages
     * @throws Exception
     */
    public function require(
        string|array $packages,
        ?OutputInterface $output = null
    ): int {
        $input = new ArrayInput([
            'command' => 'require',
            'packages' => is_string($packages) ? [$packages] : $packages,
            '--with-all-dependencies' => null
        ]);

        return $this->run($input, $output);
    }

    /**
     * @param string|string[] $packages
     * @throws Exception
     */
    public function reinstall(
        string|array $packages,
        ?OutputInterface $output = null
    ): int {
        $input = new ArrayInput([
            'command' => 'reinstall',
            'packages' => is_string($packages) ? [$packages] : $packages
        ]);

        return $this->run($input, $output);
    }

    /**
     * @param string|string[] $packages
     * @throws Exception
     */
    public function remove(
        string|array $packages,
        ?OutputInterface $output = null
    ): int {
        $input = new ArrayInput([
            'command' => 'remove',
            'packages' => is_string($packages) ? [$packages] : $packages
        ]);

        return $this->run($input, $output);
    }

    /**
     * @param string|string[] $packages
     * @throws Exception
     */
    private function run(
        InputInterface $input,
        ?OutputInterface $output = null
    ): int {
        $code = $this->composer->run($input, $output);

        return $code;
    }
}
