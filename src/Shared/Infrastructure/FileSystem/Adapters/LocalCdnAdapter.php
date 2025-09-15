<?php

declare(strict_types=1);

namespace Shared\Infrastructure\FileSystem\Adapters;

use Easy\Container\Attributes\Inject;
use League\Flysystem\UnableToCreateDirectory;
use Override;

class LocalCdnAdapter extends LocalFileSystemAdapter implements
    CdnAdapterInterface
{
    public const LOOKUP_KEY = 'local';

    private ?string $base;

    /**
     * @param string $location The location where the files are stored
     * @param bool $isSecure Whether the domain is secure or not
     * @param string $domain The domain where the files are stored
     * @return void Returns nothing
     * @throws UnableToCreateDirectory
     */
    public function __construct(
        #[Inject('config.dirs.webroot')]
        string $location,

        #[Inject('option.site.is_secure')]
        bool $isSecure = false,

        #[Inject('option.site.domain')]
        string $domain = '',
    ) {
        parent::__construct($location . '/uploads');
        $this->base = ($isSecure ? 'https://' : 'http://') . trim($domain, '/');
    }

    #[Override]
    public function isEnabled(): bool
    {
        // Local CDN is always enabled
        return true;
    }

    #[Override]
    public function getName(): string
    {
        return 'Local';
    }

    #[Override]
    public function getUrl(string $path): string
    {
        if (is_null($this->base)) {
            return '';
        }

        return $this->base
            . '/uploads/'
            . preg_replace('/(\/+)/', '/', trim($path, '/'));
    }
}
