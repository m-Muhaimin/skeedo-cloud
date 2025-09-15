<?php

declare(strict_types=1);

namespace Shared\Infrastructure\FileSystem;

use Easy\Container\Attributes\Inject;
use League\Flysystem\Visibility;
use Override;
use Ramsey\Uuid\Uuid;
use Shared\Infrastructure\FileSystem\Adapters\CdnAdapterInterface;
use Shared\Infrastructure\FileSystem\Adapters\LocalCdnAdapter;
use Shared\Infrastructure\FileSystem\Exceptions\AdapterNotFoundException;
use User\Domain\Entities\UserEntity;
use Workspace\Domain\Entities\WorkspaceEntity;

class Cdn extends FileSystem implements CdnInterface
{
    private CdnAdapterInterface $adapter;

    public function __construct(
        private CdnAdapterCollectionInterface $collection,

        #[Inject('option.cdn.adapter')]
        private ?string $adapterLookupKey = LocalCdnAdapter::LOOKUP_KEY,

        #[Inject('option.cdn.sign_urls')]
        private bool $signUrls = false,

        #[Inject('option.cdn.group_by')]
        private ?string $groupBy = null,
    ) {
        if (is_null($this->adapterLookupKey)) {
            $this->adapterLookupKey = LocalCdnAdapter::LOOKUP_KEY;
        }

        try {
            $this->adapter = $this->collection->get($this->adapterLookupKey);
        } catch (AdapterNotFoundException $th) {
            if ($adapterLookupKey == LocalCdnAdapter::LOOKUP_KEY) {
                throw $th;
            }

            $this->adapter = $this->collection->get(LocalCdnAdapter::LOOKUP_KEY);
            $this->adapterLookupKey = LocalCdnAdapter::LOOKUP_KEY;
        }

        parent::__construct($this->adapter);
    }

    #[Override]
    public function getUrl(string $path): ?string
    {
        return $this->adapter->getUrl($path);
    }

    #[Override]
    public function getAdapterLookupKey(): string
    {
        return $this->adapterLookupKey;
    }

    #[Override]
    public function generatePath(
        string $ext,
        ?WorkspaceEntity $workspace = null,
        ?UserEntity $user = null,
    ): string {
        $parts = [];

        if (
            $workspace
            && in_array($this->groupBy, ['workspace', 'workspace_user'])
        ) {
            $parts[] = (string) $workspace->getId()->getValue();
        }

        if ($user && in_array($this->groupBy, ['user', 'workspace_user'])) {
            $parts[] = (string) $user->getId()->getValue();
        }

        $parts[] = Uuid::uuid4()->toString() . '.' . trim(strtolower($ext), '.');

        return implode('/', $parts);
    }

    #[Override]
    public function write(
        string $location,
        string $contents,
        array $config = []
    ): void {
        if ($this->signUrls && !isset($config['visibility'])) {
            $config['visibility'] = Visibility::PRIVATE;
        }

        if (!isset($config['visibility'])) {
            $config['visibility'] = Visibility::PUBLIC;
        }

        parent::write($location, $contents, $config);
    }
}
