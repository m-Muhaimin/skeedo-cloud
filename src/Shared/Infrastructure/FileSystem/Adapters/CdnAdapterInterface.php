<?php

declare(strict_types=1);

namespace Shared\Infrastructure\FileSystem\Adapters;

interface CdnAdapterInterface extends AdapterInterface
{
    /**
     * Check if the adapter is enabled
     *
     * @return bool
     */
    public function isEnabled(): bool;

    /**
     * Get the name of the adapter
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Resolve file path at the public domain
     *
     * @param string $path Path (object key) of the file relative to the
     * file system's base domain
     * @return string
     */
    public function getUrl(string $path): string;
}
