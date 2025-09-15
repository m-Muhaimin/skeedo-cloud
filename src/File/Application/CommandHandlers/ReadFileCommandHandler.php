<?php

declare(strict_types=1);

namespace File\Application\CommandHandlers;

use File\Application\Commands\ReadFileCommand;
use File\Domain\Entities\FileEntity;
use File\Domain\Exceptions\FileNotFoundException;
use File\Domain\Repositories\FileRepositoryInterface;

class ReadFileCommandHandler
{
    public function __construct(
        private FileRepositoryInterface $repo
    ) {}

    /**
     * @throws FileNotFoundException
     */
    public function handle(ReadFileCommand $cmd): FileEntity
    {
        $file = $this->repo->ofId($cmd->id);
        return $file;
    }
}
