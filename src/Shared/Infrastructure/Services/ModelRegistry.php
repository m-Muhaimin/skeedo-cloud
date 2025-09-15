<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Services;

use ArrayAccess;
use Easy\Container\Attributes\Inject;
use Shared\Infrastructure\Helpers\AssetHelper;

class ModelRegistry implements ArrayAccess
{
    private ?array $registry = null;

    public function __construct(
        private AssetHelper $helper,

        #[Inject('config.dirs.root')]
        private string $root,
    ) {
        $this->populate();
    }

    public function toArray(): array
    {
        return $this->registry;
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->registry[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->registry[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->registry[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->registry[$offset]);
    }

    public function getRegistry(): array
    {
        return $this->registry;
    }

    public function save(): void
    {
        file_put_contents(
            $this->root . '/config/registry.json',
            json_encode($this->registry, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_LINE_TERMINATORS)
        );
    }

    private function populate(): void
    {
        if ($this->registry !== null) {
            return;
        }

        $this->registry = json_decode(
            file_get_contents($this->root . '/config/registry.base.json'),
            true
        ) ?? [];

        if (file_exists($this->root . '/config/registry.json')) {
            $registry = json_decode(
                file_get_contents($this->root . '/config/registry.json'),
                true
            ) ?? [];

            // Merge here
            $this->registry = $this->mergeRegistry($this->registry, $registry);
        }

        // // Apply getAssetUrl to all icon fields
        // if (isset($this->registry['directory']) && is_array($this->registry['directory'])) {
        //     foreach ($this->registry['directory'] as &$directory) {
        //         if (isset($directory['icon'])) {
        //             $directory['icon'] = $this->helper->getAssetUrl($directory['icon']);
        //         }

        //         if (isset($directory['models']) && is_array($directory['models'])) {
        //             foreach ($directory['models'] as &$model) {
        //                 if (isset($model['icon'])) {
        //                     $model['icon'] = $this->helper->getAssetUrl($model['icon']);
        //                 }

        //                 if (isset($model['provider']['icon'])) {
        //                     $model['provider']['icon'] = $this->helper->getAssetUrl($model['provider']['icon']);
        //                 }
        //             }
        //             unset($model);
        //         }
        //     }

        //     unset($directory);
        // }
    }

    /**
     * Recursively merge two registries by 'key' for arrays of objects.
     */
    private function mergeRegistry(array $base, array $override): array
    {
        foreach ($override as $key => $value) {
            if (is_array($value) && isset($base[$key])) {
                // If this is the 'directory' or 'models' array, merge by 'key'
                if (in_array($key, ['directory', 'models'])) {
                    $base[$key] = $this->mergeByKey($base[$key], $value);
                } elseif (is_array($base[$key]) && is_array($value)) {
                    $base[$key] = $this->mergeRegistry($base[$key], $value);
                } else {
                    $base[$key] = $value;
                }
            } else {
                $base[$key] = $value;
            }
        }
        return $base;
    }

    /**
     * Merge two arrays of objects by their 'key' property.
     */
    private function mergeByKey(array $baseArr, array $overrideArr): array
    {
        $result = [];
        $baseMap = [];
        foreach ($baseArr as $item) {
            if (isset($item['key'])) {
                $baseMap[$item['key']] = $item;
            }
        }
        foreach ($overrideArr as $item) {
            if (isset($item['key'])) {
                if (isset($baseMap[$item['key']])) {
                    $baseMap[$item['key']] = $this->mergeRegistry($baseMap[$item['key']], $item);
                } elseif (isset($item['custom']) && $item['custom']) {
                    $baseMap[$item['key']] = $item;
                }
            }
        }
        // Preserve order: base first, then new keys from override
        $seen = [];
        foreach ($baseArr as $item) {
            if (isset($item['key']) && isset($baseMap[$item['key']])) {
                $result[] = $baseMap[$item['key']];
                $seen[$item['key']] = true;
            }
        }
        foreach ($overrideArr as $item) {
            if (isset($item['key']) && empty($seen[$item['key']]) && isset($baseMap[$item['key']])) {
                $result[] = $item;
            }
        }
        return $result;
    }
}
