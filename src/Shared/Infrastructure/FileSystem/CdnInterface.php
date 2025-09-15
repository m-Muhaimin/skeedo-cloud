<?php

declare(strict_types=1);

namespace Shared\Infrastructure\FileSystem;

use User\Domain\Entities\UserEntity;
use Workspace\Domain\Entities\WorkspaceEntity;

interface CdnInterface extends FileSystemInterface
{
    /**
     * Resolve file path at the public domain
     *
     * @param string $path Path (object key) of the file relative to the
     * file system's base domain
     * @return string|null
     */
    public function getUrl(string $path): ?string;

    /**
     * Get the adapter lookup key
     *
     * @return string
     */
    public function getAdapterLookupKey(): string;

    /**
     * Generate a path for a file relative to the base directory
     *
     * Depending on configuration, $workspace and $user parameters can be used
     * to group files by workspace and user.
     *
     * @param string $ext
     * @param WorkspaceEntity|null $workspace
     * @param UserEntity|null $user
     * @return string
     */
    public function generatePath(
        string $ext,
        ?WorkspaceEntity $workspace = null,
        ?UserEntity $user = null,
    ): string;
}
