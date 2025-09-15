<?php

declare(strict_types=1);

namespace Ai\Application\CommandHandlers;

use Ai\Application\Commands\DeleteLibraryItemCommand;
use Ai\Domain\Entities\AbstractLibraryItemEntity;
use Ai\Domain\Exceptions\LibraryItemNotFoundException;
use Ai\Domain\Repositories\LibraryItemRepositoryInterface;
use Shared\Infrastructure\FileSystem\CdnInterface;
use Throwable;

class DeleteLibraryItemCommandHandler
{
    public function __construct(
        private LibraryItemRepositoryInterface $repo,
        private CdnInterface $cdn,
    ) {}

    /**
     * @throws LibraryItemNotFoundException
     */
    public function handle(DeleteLibraryItemCommand $cmd): void
    {
        $item = $cmd->item instanceof AbstractLibraryItemEntity
            ? $cmd->item
            : $this->repo->ofId($cmd->item);

        $this->repo->remove($item);

        foreach ($item->getFiles() as $file) {
            try {
                $this->cdn->delete($file->getObjectKey()->value);
            } catch (Throwable $e) {
                // Unable to delete file from CDN, this is not a critical error
                // and we can safely ignore it
            }
        }
    }
}
