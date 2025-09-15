<?php

declare(strict_types=1);

namespace Category\Infrastructure\Repositories\DoctrineOrm;

use Category\Domain\Entities\CategoryEntity;
use Category\Domain\Exceptions\CategoryNotFoundException;
use Category\Domain\Repositories\CategoryRepositoryInterface;
use Shared\Domain\ValueObjects\Id;
use Shared\Domain\ValueObjects\SortDirection;
use Category\Domain\ValueObjects\SortParameter;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use InvalidArgumentException;
use Override;
use RuntimeException;
use Shared\Infrastructure\Repositories\DoctrineOrm\AbstractRepository;
use Traversable;

class CategoryRepository extends AbstractRepository implements
    CategoryRepositoryInterface
{
    private const ENTITY_CLASS = CategoryEntity::class;
    private const ALIAS = 'category';
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
    public function add(CategoryEntity $category): static
    {
        $this->em->persist($category);
        return $this;
    }

    #[Override]
    public function remove(CategoryEntity $category): static
    {
        $this->em->remove($category);
        return $this;
    }

    #[Override]
    public function ofId(Id $id): CategoryEntity
    {
        $object = $this->em->find(self::ENTITY_CLASS, $id);

        if ($object instanceof CategoryEntity) {
            return $object;
        }

        throw new CategoryNotFoundException($id);
    }

    #[Override]
    public function search(string $query): static
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($query) {
                $qb->andWhere(
                    $qb->expr()->orX(
                        self::ALIAS . '.title.value LIKE :search'
                    )
                )->setParameter('search', '%' . $query . '%');
            }
        );
    }

    #[Override]
    public function sort(
        SortDirection $dir,
        ?SortParameter $param = null
    ): static {
        $cloned = $this->doSort($dir, $this->getSortKey($param));
        $cloned->sortParameter = $param;

        return $cloned;
    }

    #[Override]
    public function startingAfter(CategoryEntity $cursor): Traversable
    {
        return $this->doStartingAfter(
            $cursor->getId(),
            $this->getCompareValue($cursor)
        );
    }

    #[Override]
    public function endingBefore(CategoryEntity $cursor): Traversable
    {
        return $this->doEndingBefore(
            $cursor->getId(),
            $this->getCompareValue($cursor)
        );
    }

    /**
     * Returns the sort key based on the given SortParameter.
     *
     * @param SortParameter $param The sort parameter.
     * @return null|string The sort key or null if the sort parameter is not 
     * recognized.
     */
    private function getSortKey(
        ?SortParameter $param
    ): ?string {
        return match ($param) {
            SortParameter::ID => 'id.value',
            SortParameter::TITLE => 'title.value',
            SortParameter::CREATED_AT => 'createdAt',
            SortParameter::UPDATED_AT => 'updatedAt',
            default => null
        };
    }

    /**
     * Returns the compare value based on the current sort parameter 
     * and the given CategoryEntity.
     *
     * @param CategoryEntity $cursor The category entity to compare.
     * @return null|string|DateTimeInterface The compare value or null if the 
     * sort parameter is not recognized.
     */
    private function getCompareValue(
        CategoryEntity $cursor
    ): null|string|DateTimeInterface {
        return match ($this->sortParameter) {
            SortParameter::ID => $cursor->getId()->getValue()->getBytes(),
            SortParameter::TITLE => $cursor->getTitle()->value,
            SortParameter::CREATED_AT => $cursor->getCreatedAt(),
            SortParameter::UPDATED_AT => $cursor->getUpdatedAt(),
            default => null
        };
    }
}
