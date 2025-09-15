<?php

declare(strict_types=1);

namespace File\Infrastructure;

use File\Domain\Entities\AbstractFileEntity;
use Shared\Infrastructure\FileSystem\CdnInterface;

class FileService
{
    public function __construct(
        private CdnInterface $cdn
    ) {}

    public function getFileContents(AbstractFileEntity $file): string
    {
        return $this->cdn->read($file->getObjectKey()->value);
    }
}
