<?php

declare(strict_types=1);

namespace Shared\Infrastructure\FileSystem\Adapters;

use League\Flysystem\Local\LocalFilesystemAdapter as BaseAdapter;

class LocalFileSystemAdapter extends BaseAdapter implements
    AdapterInterface
{
}
