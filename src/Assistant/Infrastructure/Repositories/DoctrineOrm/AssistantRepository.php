<?php

declare(strict_types=1);

namespace Assistant\Infrastructure\Repositories\DoctrineOrm;

use Assistant\Domain\Entities\AssistantEntity;
use Assistant\Domain\Exceptions\AssistantNotFoundException;
use Assistant\Domain\Repositories\AssistantRepositoryInterface;
use Shared\Domain\ValueObjects\Id;
use Shared\Domain\ValueObjects\SortDirection;
use Assistant\Domain\ValueObjects\SortParameter;
use Assistant\Domain\ValueObjects\Status;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use InvalidArgumentException;
use Override;
use RuntimeException;
use Shared\Infrastructure\Repositories\DoctrineOrm\AbstractRepository;
use Traversable;

class AssistantRepository extends AbstractRepository implements
    AssistantRepositoryInterface
{
    private const ENTITY_CLASS = AssistantEntity::class;
    private const ALIAS = 'assistant';
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
    public function add(AssistantEntity $assistant): static
    {
        $this->em->persist($assistant);
        return $this;
    }

    #[Override]
    public function remove(AssistantEntity $assistant): static
    {
        $this->em->remove($assistant);
        return $this;
    }

    #[Override]
    public function ofId(Id $id): AssistantEntity
    {
        $object = $this->em->find(self::ENTITY_CLASS, $id);

        if ($object instanceof AssistantEntity) {
            return $object;
        }

        throw new AssistantNotFoundException($id);
    }

    #[Override]
    public function filterByStatus(Status $status): static
    {
        return $this->filter(static function (QueryBuilder $qb) use ($status) {
            $qb->andWhere(self::ALIAS . '.status = :status')
                ->setParameter(':status', $status->value, Types::SMALLINT);
        });
    }

    #[Override]
    public function filterById(Id|array $ids): static
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }

        $ids = array_filter(
            $ids,
            static fn($id) => $id instanceof Id
        );

        $ids = array_map(
            static fn(Id $id) => $id->getValue()->getBytes(),
            $ids
        );

        return $this->filter(static function (QueryBuilder $qb) use ($ids) {
            $qb->andWhere(self::ALIAS . '.id.value IN (:ids)')
                ->setParameter(':ids', $ids);
        });
    }

    #[Override]
    public function search(string $query): static
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($query) {
                $qb->andWhere(
                    $qb->expr()->orX(
                        self::ALIAS . '.name.value LIKE :search',
                        self::ALIAS . '.description.value LIKE :search',
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
    public function startingAfter(AssistantEntity $cursor): Traversable
    {
        return $this->doStartingAfter(
            $cursor->getId(),
            $this->getCompareValue($cursor)
        );
    }

    #[Override]
    public function endingBefore(AssistantEntity $cursor): Traversable
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
            SortParameter::CREATED_AT => 'createdAt',
            SortParameter::UPDATED_AT => 'updatedAt',
            SortParameter::NAME => 'name.value',
            SortParameter::POSITION => 'position.value',
            default => null
        };
    }

    /**
     * Returns the compare value based on the current sort parameter 
     * and the given AssistantEntity.
     *
     * @param AssistantEntity $cursor The assistant entity to compare.
     * @return null|string|DateTimeInterface The compare value or null if the 
     * sort parameter is not recognized.
     */
    private function getCompareValue(
        AssistantEntity $cursor
    ): null|string|DateTimeInterface {
        return match ($this->sortParameter) {
            SortParameter::ID => $cursor->getId()->getValue()->getBytes(),
            SortParameter::CREATED_AT => $cursor->getCreatedAt(),
            SortParameter::UPDATED_AT => $cursor->getUpdatedAt(),
            SortParameter::NAME => $cursor->getName()->value,
            SortParameter::POSITION => $cursor->getPosition()->value,
            default => null
        };
    }
}
