<?php

declare(strict_types=1);

namespace Shared\Infrastructure;

use Easy\Container\Attributes\Inject;
use Psr\Cache\CacheItemPoolInterface;
use Psr\SimpleCache\CacheInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class CacheManager
{
    public function __construct(
        private CacheItemPoolInterface $cacheItemPool,
        private CacheInterface $cache,

        #[Inject('config.dirs.cache')]
        private string $cacheDir,
    ) {
    }

    public function clearCache(): void
    {
        $this->cacheItemPool->clear();
        $this->cache->clear();
        $this->clearCacheDir();
    }

    private function clearCacheDir(): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $this->cacheDir,
                RecursiveDirectoryIterator::SKIP_DOTS
            ),

            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $path) {
            if ($path->isDir()) {
                rmdir($path->getPathname());
            } elseif ($path->getFilename() != '.gitkeep') {
                unlink($path->getPathname());
            }
        }
    }
}
