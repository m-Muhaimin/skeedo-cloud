<?php

declare(strict_types=1);

namespace Plugin\Infrastructure;

use ArrayIterator;
use DirectoryIterator;
use Iterator;
use UnexpectedValueException;
use ValueError;

class PluginFinder
{
    /**
     * Find all plugins in the given directory sorted by creation time.
     * 
     * @param string $dir The directory to search for plugins.
     * @return Iterator<int,string>
     */
    public function findPlugins(string $dir): Iterator
    {
        $pluginDirs = new ArrayIterator();

        try {
            $di = new DirectoryIterator($dir);
        } catch (UnexpectedValueException | ValueError $th) {
            return $pluginDirs;
        }

        foreach ($di as $vendor) {
            if ($vendor->isDir() && !$vendor->isDot()) {
                $iterator = new DirectoryIterator($vendor->getPathname());

                foreach ($iterator as $package) {
                    $composerJsonFile = $package->getPathname()
                        . '/composer.json';

                    if (file_exists($composerJsonFile)) {
                        $pluginDirs->append($package->getPathname());
                    }
                }
            }
        }

        $pluginDirs->uasort(
            fn (string $a, string $b): int =>
            filectime($b) <=> filectime($a)
        );

        return $pluginDirs;
    }
}
