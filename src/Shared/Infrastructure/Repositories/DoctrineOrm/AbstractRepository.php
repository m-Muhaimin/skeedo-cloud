<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Repositories\DoctrineOrm;

use ArrayIterator;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use InvalidArgumentException;
use Iterator;
use LogicException;
use Psr\Cache\InvalidArgumentException as CacheInvalidArgumentException;
use RuntimeException;
use Shared\Domain\Repositories\RepositoryInterface;
use Shared\Domain\ValueObjects\Id;
use Shared\Domain\ValueObjects\MaxResults;
use Shared\Domain\ValueObjects\SortDirection;

/**
 * This abstract DoctrineORM repository is meant to every DDoctrineORM
 * repository implementation. This abstract repository implements the
 * RepositoryInterface contract in an immutable way.
 */
abstract class AbstractRepository implements RepositoryInterface
{
    private ?SortDirection $sortDirection = null;
    private ?string $sortKey = null;

    /**
     * Visibility set to private for not exposing the query builder to child
     * classes. This guarantees that the original reference is not modified
     * in child classes.
     */
    private QueryBuilder $qb;

    /**
     * @param EntityManagerInterface $em
     * @param string $entityClass
     * @param string $alias
     * @return void
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function __construct(
        /** Doctrine's Entity Manager. Child classes may use it. */
        protected EntityManagerInterface $em,
        string $entityClass,
        private string $alias
    ) {
        $this->qb = $this->em->createQueryBuilder()
            ->select($alias)
            ->from($entityClass, $alias);

        // Check if entity supports soft deletes
        $metadata = $em->getClassMetadata($entityClass);
        if ($metadata->hasField('deletedAt')) {
            $this->qb->andWhere($alias . '.deletedAt IS NULL');
        }
    }

    /**
     * @inheritDoc
     */
    public function getIterator(): Iterator
    {
        yield from new Paginator($this->qb->getQuery());
    }

    /**
     * @inheritDoc
     */
    public function slice(int $start, int $size = 20): RepositoryInterface
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($start, $size) {
                $qb->setFirstResult($start)->setMaxResults($size);
            }
        );
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        $paginator = new Paginator($this->qb->getQuery());
        return $paginator->count();
    }

    /**
     * @inheritDoc
     */
    public function flush(): void
    {
        $this->em->flush();
    }

    /** @inheritDoc */
    public function setMaxResults(MaxResults $maxResults): static
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($maxResults) {
                $qb->setMaxResults($maxResults->value);
            }
        );
    }

    /**
     * @param SortDirection $dir 
     * @param null|string $sortKey 
     * @return static 
     */
    protected function doSort(
        SortDirection $dir,
        ?string $sortKey = null
    ): static {
        $repoAlias = $this->alias;
        $orderBy = null;

        if ($sortKey) {
            if (str_contains($sortKey, '/')) {
                $parts = explode('/', $sortKey, 2);
                $orderBy = $parts[0] . '.' . $parts[1];
            } else {
                $orderBy = $repoAlias . '.' . $sortKey;
            }
        }

        $cloned = $this->filter(
            static function (QueryBuilder $qb) use ($orderBy, $dir, $repoAlias) {
                if ($orderBy) {
                    $qb->orderBy($orderBy, $dir->value);
                }

                $qb->addOrderBy($repoAlias . '.id.value', $dir->value);
            }
        );

        $cloned->sortDirection = $dir;
        $cloned->sortKey = $sortKey;

        return $cloned;
    }

    /**
     * @throws LogicException
     * @throws CacheInvalidArgumentException
     * @throws ORMException
     */
    protected function doStartingAfter(
        Id $cursorId,
        null|string|int|DateTimeInterface $compareTo = null
    ): Iterator {
        $dir = $this->sortDirection;
        $sortKey = $this->sortKey;
        $alias =  $this->alias;
        $repoAlias = $this->alias;

        if ($sortKey && str_contains($sortKey, '/')) {
            $parts = explode('/', $sortKey, 2);
            $alias = $parts[0];
            $sortKey = $parts[1];
        }

        $cloned = $this->filter(
            static function (QueryBuilder $qb) use (
                $repoAlias,
                $alias,
                $dir,
                $sortKey,
                $compareTo,
                $cursorId,

            ) {
                $op = $dir === SortDirection::DESC ? '<' : '>';

                if ($sortKey) {
                    $qb->andWhere(
                        $qb->expr()->orX(
                            $op == '>'
                                ? $qb->expr()->gt($alias . '.' . $sortKey, ':compareTo')
                                : $qb->expr()->lt($alias . '.' . $sortKey, ':compareTo'),
                            $qb->expr()->andX(
                                $qb->expr()->eq($alias . '.' . $sortKey, ':compareTo'),
                                $op == '>'
                                    ? $qb->expr()->gt($repoAlias . '.id.value', ':id')
                                    : $qb->expr()->lt($repoAlias . '.id.value', ':id')
                            )
                        )
                    )->setParameter('compareTo', $compareTo);
                } else {
                    $qb->andWhere(
                        $op == '>'
                            ? $qb->expr()->gt($repoAlias . '.id.value', ':id')
                            : $qb->expr()->lt($repoAlias . '.id.value', ':id')
                    );
                }

                $qb->setParameter('id', $cursorId->getValue()->getBytes());
            }
        );

        yield from new Paginator($cloned->qb->getQuery());
    }

    /**
     * @param Id $cursorId
     * @param null|string|int|DateTimeInterface $compareTo
     * @return Iterator
     */
    protected function doEndingBefore(
        Id $cursorId,
        null|string|int|DateTimeInterface $compareTo = null
    ): Iterator {
        $dir = $this->sortDirection;
        $sortKey = $this->sortKey;
        $alias =  $this->alias;
        $repoAlias = $this->alias;

        if ($sortKey && str_contains($sortKey, '/')) {
            $parts = explode('/', $sortKey, 2);
            $alias = $parts[0];
            $sortKey = $parts[1];
        }

        $cloned = $this->filter(
            static function (QueryBuilder $qb) use (
                $repoAlias,
                $alias,
                $dir,
                $sortKey,
                $compareTo,
                $cursorId
            ) {
                if ($sortKey) {
                    $qb->orderBy($alias . '.' . $sortKey, $dir->getOpposite()->value);
                    $qb->addOrderBy($repoAlias . '.id.value', $dir->getOpposite()->value);
                } else {
                    $qb->orderBy($repoAlias . '.id.value', $dir->getOpposite()->value);
                }

                $op = $dir->getOpposite() === SortDirection::ASC ? '>' : '<';

                if ($sortKey) {
                    $qb->andWhere(
                        $qb->expr()->orX(
                            $op == '>'
                                ? $qb->expr()->gt($alias . '.' . $sortKey, ':compareTo')
                                : $qb->expr()->lt($alias . '.' . $sortKey, ':compareTo'),
                            $qb->expr()->andX(
                                $qb->expr()->eq($alias . '.' . $sortKey, ':compareTo'),
                                $op == '>'
                                    ? $qb->expr()->gt($repoAlias . '.id.value', ':id')
                                    : $qb->expr()->lt($repoAlias . '.id.value', ':id')
                            )
                        )
                    )->setParameter('compareTo', $compareTo);
                } else {
                    $qb->andWhere(
                        $op == '>'
                            ? $qb->expr()->gt($repoAlias . '.id.value', ':id')
                            : $qb->expr()->lt($repoAlias . '.id.value', ':id')
                    );
                }

                $qb->setParameter('id', $cursorId->getValue()->getBytes());
            }
        );

        $iterator = new Paginator($cloned->qb->getQuery());
        $iterator = new ArrayIterator(
            array_reverse(iterator_to_array($iterator))
        );

        yield from $iterator;
    }

    /**
     * Filters the repository using the query builder
     *
     * Clones this repository and returns the new instance with the modified
     * query builder, so the original reference is preserved.
     *
     * @param callable $filter
     * @return static
     */
    protected function filter(callable $filter): self
    {
        $cloned = clone $this;
        $filter($cloned->qb);

        return $cloned;
    }

    /**
     * Returns a cloned instance of the query builder.
     * Use this to perform single result queries.
     *
     * @return QueryBuilder
     */
    protected function query(): QueryBuilder
    {
        return clone $this->qb;
    }

    /**
     * Allow cloning only from this scope.
     * Also always clone the query builder.
     *
     * @return void
     */
    protected function __clone()
    {
        $this->qb = clone $this->qb;
    }
}
