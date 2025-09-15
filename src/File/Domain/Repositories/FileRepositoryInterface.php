<?php

declare(strict_types=1);

namespace File\Domain\Repositories;

use File\Domain\Entities\FileEntity;
use File\Domain\Exceptions\FileNotFoundException;
use Shared\Domain\Repositories\RepositoryInterface;
use Shared\Domain\ValueObjects\Id;

/**
 * This interface represents a File Repository.
 * It extends the RepositoryInterface.
 */
interface FileRepositoryInterface extends RepositoryInterface
{

    /**
     * Retrieves a file by its ID.
     *
     * @param Id $id The ID of the file.
     * @return FileEntity Returns the file entity
     * @throws FileNotFoundException If the file is not found.
     */
    public function ofId(Id $id): FileEntity;
}
