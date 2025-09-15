<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Bootstrappers;

use Application;
use Easy\Container\Attributes\Inject;
use Psr\Container\ContainerInterface;
use Shared\Infrastructure\BootstrapperInterface;
use Shared\Infrastructure\FileSystem\Adapters\LocalCdnAdapter;
use Shared\Infrastructure\FileSystem\Adapters\LocalFileSystemAdapter;
use Shared\Infrastructure\FileSystem\Cdn;
use Shared\Infrastructure\FileSystem\CdnAdapterCollection;
use Shared\Infrastructure\FileSystem\CdnAdapterCollectionInterface;
use Shared\Infrastructure\FileSystem\CdnInterface;
use Shared\Infrastructure\FileSystem\FileSystem;
use Shared\Infrastructure\FileSystem\FileSystemInterface;

class FileSystemBootstrapper implements BootstrapperInterface
{
    public function __construct(
        private Application $app,
        private ContainerInterface $container,

        #[Inject('config.dirs.root')]
        private string $rootDir,
    ) {
    }

    /** @inheritDoc */
    public function bootstrap(): void
    {
        $collection = new CdnAdapterCollection($this->container);
        $collection->add(LocalCdnAdapter::LOOKUP_KEY, LocalCdnAdapter::class);

        $this->app
            ->set(FileSystemInterface::class, $this->getFs())
            ->set(CdnAdapterCollectionInterface::class, $collection)
            ->set(CdnInterface::class, Cdn::class);
    }

    private function getFs(): FileSystemInterface
    {
        $adapter = new LocalFileSystemAdapter(
            $this->rootDir,
            linkHandling: LocalFilesystemAdapter::SKIP_LINKS,
        );

        return new FileSystem($adapter);
    }
}
