<?php

declare(strict_types=1);

namespace Shared\Infrastructure\FileSystem;

use IteratorAggregate;
use Shared\Infrastructure\FileSystem\Adapters\CdnAdapterInterface;

interface CdnAdapterCollectionInterface extends IteratorAggregate
{
    /**
     * Add a CDN implementation to the collection.
     * 
     * @param string $key The key to register the implementation under.
     * @param class-string<CdnAdapterInterface>|CdnAdapterInterface $adapter 
     * The adapter to register.
     * @return static
     */
    public function add(
        string $key,
        string|CdnAdapterInterface $adapter
    ): static;

    /**
     * Get a CDN implementation from the collection.
     * 
     * @param string $key The key to retrieve the implementation for.
     * @return CdnAdapterInterface
     */
    public function get(string $key): CdnAdapterInterface;
}
