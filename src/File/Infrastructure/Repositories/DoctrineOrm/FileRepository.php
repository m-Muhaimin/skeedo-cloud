<?php

declare(strict_types=1);

namespace File\Infrastructure\Repositories\DoctrineOrm;

use File\Domain\Entities\FileEntity;
use File\Domain\Exceptions\FileNotFoundException;
use File\Domain\Repositories\FileRepositoryInterface;
use Shared\Domain\ValueObjects\Id;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Override;
use RuntimeException;
use Shared\Infrastructure\Repositories\DoctrineOrm\AbstractRepository;

class FileRepository extends AbstractRepository implements
    FileRepositoryInterface
{
    private const ENTITY_CLASS = FileEntity::class;
    private const ALIAS = 'file';

    /**
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, self::ENTITY_CLASS, self::ALIAS);
    }

    #[Override]
    public function ofId(Id $id): FileEntity
    {
        $object = $this->em->find(self::ENTITY_CLASS, $id);

        if ($object instanceof FileEntity) {
            return $object;
        }

        throw new FileNotFoundException($id);
    }
}
