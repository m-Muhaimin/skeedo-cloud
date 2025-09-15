<?php

declare(strict_types=1);

namespace Shared\Infrastructure\FileSystem;

use League\Flysystem\Filesystem as BaseFilesystem;

class FileSystem extends BaseFilesystem implements FileSystemInterface
{
}
