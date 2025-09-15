<?php

declare(strict_types=1);

namespace Workspace\Infrastructure\Repositories\DoctrineOrm;

use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\QueryBuilder;
use InvalidArgumentException;
use Iterator;
use LogicException;
use Override;
use Psr\Cache\InvalidArgumentException as CacheInvalidArgumentException;
use RuntimeException;
use Shared\Domain\ValueObjects\Id;
use Shared\Domain\ValueObjects\SortDirection;
use Shared\Infrastructure\Repositories\DoctrineOrm\AbstractRepository;
use Workspace\Domain\Entities\WorkspaceEntity;
use Workspace\Domain\Exceptions\WorkspaceNotFoundException;
use Workspace\Domain\Repositories\WorkspaceRepositoryInterface;
use Workspace\Domain\ValueObjects\SortParameter;

class WorkspaceRepository extends AbstractRepository implements
    WorkspaceRepositoryInterface
{
    private const ENTITY_CLASS = WorkspaceEntity::class;
    private const ALIAS = 'workspace';

    private ?SortParameter $sortParameter = null;

    /**
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, self::ENTITY_CLASS, self::ALIAS);
    }

    #[Override]
    public function add(WorkspaceEntity $workspace): static
    {
        $this->em->persist($workspace);
        return $this;
    }

    #[Override]
    public function remove(WorkspaceEntity $workspace): static
    {
        $this->em->remove($workspace);
        return $this;
    }

    #[Override]
    public function ofId(Id $id): WorkspaceEntity
    {
        $object = $this->em->find(self::ENTITY_CLASS, $id);

        if ($object instanceof WorkspaceEntity) {
            return $object;
        }

        throw new WorkspaceNotFoundException($id);
    }

    #[Override]
    public function sort(SortDirection $dir, ?SortParameter $sortParameter = null): static
    {
        $cloned = $this->doSort($dir, $this->getSortKey($sortParameter));
        $cloned->sortParameter = $sortParameter;

        return $cloned;
    }

    /**
     * @throws LogicException
     * @throws CacheInvalidArgumentException
     * @throws ORMException
     */
    #[Override]
    public function startingAfter(WorkspaceEntity $cursor): Iterator
    {
        return $this->doStartingAfter(
            $cursor->getId(),
            $this->getCompareValue($cursor)
        );
    }

    #[Override]
    public function endingBefore(WorkspaceEntity $cursor): Iterator
    {
        return $this->doEndingBefore(
            $cursor->getId(),
            $this->getCompareValue($cursor)
        );
    }

    #[Override]
    public function hasSubscription($has = true): self
    {
        return $this->filter(static function (QueryBuilder $qb) use ($has) {
            $qb->andWhere(self::ALIAS . '.subscription IS ' . ($has ? 'NOT' : '') . ' NULL');;
        });
    }

    #[Override]
    public function search(string $terms): static
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($terms) {
                $qb->andWhere(
                    $qb->expr()->orX(
                        self::ALIAS . '.name.value LIKE :search'
                    )
                )->setParameter('search', $terms . '%');
            }
        );
    }

    /**
     * Returns the sort key based on the given SortParameter.
     *
     * @param SortParameter|null $param The sort parameter.
     * @return string|null The sort key.
     */
    private function getSortKey(?SortParameter $param): ?string
    {
        return match ($param) {
            SortParameter::ID => 'id.value',
            SortParameter::CREATED_AT => 'createdAt',
            SortParameter::UPDATED_AT => 'updatedAt',
            SortParameter::NAME => 'name.value',
            default => null,
        };
    }

    /**
     * Returns the compare value 
     * based on the current sort parameter and the given WorkspaceEntity.
     *
     * @param WorkspaceEntity $cursor The workspace entity to compare.
     * @return null|string|DateTimeInterface The compare value.
     */
    private function getCompareValue(
        WorkspaceEntity $cursor
    ): null|string|DateTimeInterface {
        return match ($this->sortParameter) {
            SortParameter::ID => $cursor->getId()->getValue()->getBytes(),
            SortParameter::CREATED_AT => $cursor->getCreatedAt(),
            SortParameter::UPDATED_AT => $cursor->getUpdatedAt(),
            SortParameter::NAME => $cursor->getName()->value,
            default => null
        };
    }
}
