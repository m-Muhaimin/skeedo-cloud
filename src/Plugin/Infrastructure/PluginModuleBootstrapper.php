<?php

declare(strict_types=1);

namespace Plugin\Infrastructure;

use Application;
use Easy\Container\Attributes\Inject;
use Plugin\Domain\Repositories\PluginRepositoryInterface;
use Plugin\Infrastructure\Repositories\InMemory\PluginRepository;
use Psr\Container\ContainerInterface;
use Shared\Infrastructure\BootstrapperInterface;
use Shared\Infrastructure\Twig\ThemeExtension;
use Throwable;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class PluginModuleBootstrapper implements BootstrapperInterface
{
    public function __construct(
        private Application $app,
        private ContainerInterface $container,

        private Environment $twig,
        private FilesystemLoader $loader,
        private ThemeExtension $themeExtension,

        #[Inject('config.dirs.extensions')]
        private string $extDir,

        #[Inject('option.theme')]
        private string $theme = 'heyaikeedo/default',

        #[Inject('config.enable_debugging')]
        private bool $enableDebugging = false,
    ) {}

    public function bootstrap(): void
    {
        $this->app->set(
            PluginRepositoryInterface::class,
            PluginRepository::class,
        );

        $this->loadPlugins();
        $this->loadTheme();
    }

    public function loadPlugins()
    {
        $finder = new PluginFinder();
        $plugins = $finder->findPlugins($this->extDir);

        /**  @var PluginLoader */
        $loader = $this->container->get(PluginLoader::class);

        foreach ($plugins as $plugin) {
            try {
                $loader->load($plugin);
            } catch (Throwable $th) {
                if ($this->enableDebugging) {
                    throw $th;
                }

                // Production environment. Skip plugin.
            }
        }
    }

    public function loadTheme()
    {
        $this->twig->addExtension($this->themeExtension);

        if (file_exists($this->extDir . '/' . $this->theme)) {
            $this->loader->addPath(
                $this->extDir . '/' . $this->theme,
                'theme'
            );
        }
    }
}
