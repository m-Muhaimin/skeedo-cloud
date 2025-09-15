<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Helpers;

use Easy\Container\Attributes\Inject;
use stdClass;

class AssetHelper
{
    private object $manifest;

    public function __construct(
        #[Inject('config.dirs.webroot')]
        string $webroot
    ) {
        $this->manifest = file_exists($webroot . '/.vite/manifest.json')
            ? json_decode(file_get_contents($webroot . '/.vite/manifest.json'))
            : new stdClass();
    }

    public function getAssetUrl(string $asset): string
    {
        // Return as is if it's a full valid URL
        if (filter_var($asset, FILTER_VALIDATE_URL)) {
            return $asset;
        }

        if (env('HMR')) {
            return rtrim(env('ASSETS_SERVER', 'http://localhost:5173'), '/')
                . '/' . ltrim($asset, "/");
        }

        return ($this->manifest->{ltrim($asset, "/")}->file ?? $asset);
    }
}
