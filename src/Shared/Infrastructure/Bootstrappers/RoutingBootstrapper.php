<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Bootstrappers;

use Application;
use Easy\Container\Attributes\Inject;
use Easy\Router\Dispatcher;
use Aikeedo\Integrity\Handler;
use Easy\Router\Mapper\AttributeMapper;
use Easy\Router\Mapper\SimpleMapper;
use Override;
use Psr\Cache\CacheItemPoolInterface;
use Shared\Infrastructure\BootstrapperInterface;

class RoutingBootstrapper implements BootstrapperInterface
{
    public function __construct(
        private Application $app,
        private Dispatcher $dispatcher,
        #[Inject('config.dirs.src')]
        private string $routeDir,
        #[Inject('config.enable_debugging')]
        private bool $enableDebugging = false,
        #[Inject('config.enable_caching')]
        private bool $enableCaching = false,
        private ?CacheItemPoolInterface $cache = null,
    ) {}

    #[Override]
    public function bootstrap(): void
    {
        $simpleMapper = new SimpleMapper();
        $attributeMapper = $this->getAttributeMapper();

        $this->dispatcher
            ->pushMapper($attributeMapper)
            ->pushMapper($simpleMapper);

        $simpleMapper->map('POST', '/rc', Handler::class);

        $this->dispatcher
            ->addMatchType('locale', '[a-z]{2}-[A-Z]{2}');

        $this->app
            ->set(AttributeMapper::class, $attributeMapper)
            ->set(SimpleMapper::class, $simpleMapper);
    }

    private function getAttributeMapper(): AttributeMapper
    {
        $mapper = new AttributeMapper($this->cache);
        $mapper->addPath($this->routeDir);

        $this->enableCaching && !$this->enableDebugging
            ? $mapper->enableCaching()
            : $mapper->disableCaching();

        return $mapper;
    }
}
