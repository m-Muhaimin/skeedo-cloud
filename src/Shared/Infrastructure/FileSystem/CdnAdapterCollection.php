<?php

declare(strict_types=1);

namespace Shared\Infrastructure\FileSystem;

use Override;
use Psr\Container\ContainerInterface;
use Shared\Infrastructure\FileSystem\Adapters\CdnAdapterInterface;
use Shared\Infrastructure\FileSystem\Exceptions\AdapterNotFoundException;
use Traversable;

class CdnAdapterCollection implements CdnAdapterCollectionInterface
{
    /** @var array<string,class-string<CdnAdapterInterface>|CdnAdapterInterface> */
    private array $adapters = [];

    public function __construct(
        private ContainerInterface $container,
    ) {
    }

    #[Override]
    public function add(string $key, string|CdnAdapterInterface $adapter): static
    {
        $this->adapters[$key] = $adapter;
        return $this;
    }

    #[Override]
    public function get(string $key): CdnAdapterInterface
    {
        if (!isset($this->adapters[$key])) {
            throw new AdapterNotFoundException($key);
        }

        $adapter = $this->adapters[$key];

        if ($adapter instanceof CdnAdapterInterface) {
            return $adapter;
        }

        $adapter = $this->resolveAdapter($adapter);
        $this->adapters[$key] = $adapter;

        return $adapter;
    }

    #[Override]
    public function getIterator(): Traversable
    {
        foreach ($this->adapters as $key => $adapter) {
            yield $key => $this->get($key);
        }
    }

    private function resolveAdapter(string $adapter): CdnAdapterInterface
    {
        if (is_string($adapter)) {
            $adapter = $this->container->get($adapter);
        }

        if (!($adapter instanceof CdnAdapterInterface)) {
            throw new \RuntimeException(
                "Adapter `{$adapter}` is not an instance of "
                    . CdnAdapterInterface::class
            );
        }

        return $adapter;
    }
}
