<?php

declare(strict_types=1);

namespace Preset\Infrastructure\DoctrineOrm;

use Category\Domain\Entities\CategoryEntity;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use DomainException;
use InvalidArgumentException;
use Iterator;
use Override;
use Preset\Domain\Entities\PresetEntity;
use Preset\Domain\Exceptions\PresetNotFoundException;
use Preset\Domain\Repositories\PresetRepositoryInterface;
use Preset\Domain\ValueObjects\SortParameter;
use Preset\Domain\ValueObjects\Status;
use Preset\Domain\ValueObjects\Template;
use Preset\Domain\ValueObjects\Type;
use RuntimeException;
use Shared\Domain\ValueObjects\Id;
use Shared\Domain\ValueObjects\SortDirection;
use Shared\Infrastructure\Repositories\DoctrineOrm\AbstractRepository;

class PresetRepository extends AbstractRepository implements
    PresetRepositoryInterface
{
    private const ENTITY_CLASS = PresetEntity::class;
    private const ALIAS = 'preset';
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
    public function add(PresetEntity $preset): static
    {
        $this->em->persist($preset);
        return $this;
    }

    #[Override]
    public function remove(PresetEntity $preset): static
    {
        $this->em->remove($preset);
        return $this;
    }

    #[Override]
    public function ofId(Id $id): PresetEntity
    {
        $object = $this->em->find(self::ENTITY_CLASS, $id);

        if ($object instanceof PresetEntity) {
            return $object;
        }

        throw new PresetNotFoundException($id);
    }

    #[Override]
    public function ofTemplate(Template $template): ?PresetEntity
    {
        if ($template->value === null) {
            return null;
        }

        try {
            $object = $this->query()
                ->where(self::ALIAS . '.template.value = :template')
                ->setParameter(':template', $template->value)
                ->getQuery()
                ->getSingleResult();
        } catch (NoResultException $e) {
            return null;
        } catch (NonUniqueResultException $e) {
            throw new DomainException('More than one result found');
        }

        return $object;
    }

    #[Override]
    public function filterByStatus(Status $status): static
    {
        return $this->filter(static function (QueryBuilder $qb) use ($status) {
            $qb->andWhere(self::ALIAS . '.status = :status')
                ->setParameter(':status', $status->value, Types::INTEGER);
        });
    }

    #[Override]
    public function filterByType(Type $type): static
    {
        return $this->filter(static function (QueryBuilder $qb) use ($type) {
            $qb->andWhere(self::ALIAS . '.type = :type')
                ->setParameter(':type', $type->value, Types::STRING);
        });
    }

    #[Override]
    public function filterByLock(bool $isLocked): static
    {
        return $this->filter(static function (QueryBuilder $qb) use ($isLocked) {
            $qb->andWhere(self::ALIAS . '.isLocked = :isLocked')
                ->setParameter(':isLocked', $isLocked, Types::BOOLEAN);
        });
    }

    #[Override]
    public function filterByCategory(Id|CategoryEntity $category): static
    {
        $id = $category instanceof CategoryEntity
            ? $category->getId()
            : $category;

        return $this->filter(static function (QueryBuilder $qb) use ($id) {
            $qb->andWhere(self::ALIAS . '.category = :categoryId')
                ->setParameter(
                    ':categoryId',
                    $id->getValue()->getBytes(),
                    Types::STRING
                );
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
                        self::ALIAS . '.title.value LIKE :search',
                        self::ALIAS . '.description.value LIKE :search'
                    )
                )->setParameter('search', '%' . $query . '%');
            }
        );
    }

    #[Override]
    public function sort(
        SortDirection $dir,
        ?SortParameter $sortParameter = null
    ): static {
        $cloned = $this->doSort($dir, $this->getSortKey($sortParameter));
        $cloned->sortParameter = $sortParameter;

        return $cloned;
    }

    #[Override]
    public function startingAfter(PresetEntity $cursor): Iterator
    {
        return $this->doStartingAfter(
            $cursor->getId(),
            $this->getCompareValue($cursor)
        );
    }

    #[Override]
    public function endingBefore(PresetEntity $cursor): Iterator
    {
        return $this->doEndingBefore(
            $cursor->getId(),
            $this->getCompareValue($cursor)
        );
    }

    /**
     * Returns the sort key based on the given SortParameter.
     *
     * @param SortParameter|null $param The SortParameter to determine the sort 
     * key.
     * @return string|null The sort key corresponding to the SortParameter, or 
     * null if no match is found.
     */
    private function getSortKey(
        ?SortParameter $param
    ): ?string {
        return match ($param) {
            SortParameter::ID => 'id.value',
            SortParameter::TITLE => 'title.value',
            SortParameter::CREATED_AT => 'createdAt',
            SortParameter::UPDATED_AT => 'updatedAt',
            SortParameter::POSITION => 'position.value',
            default => null
        };
    }

    /**
     * Get the compare value based on the sort parameter.
     *
     * @param PresetEntity $cursor The cursor entity.
     * @return null|string|DateTimeInterface The compare value.
     */
    private function getCompareValue(
        PresetEntity $cursor
    ): null|string|DateTimeInterface {
        return match ($this->sortParameter) {
            SortParameter::ID => $cursor->getId()->getValue()->getBytes(),
            SortParameter::TITLE => $cursor->getTitle()->value,
            SortParameter::CREATED_AT => $cursor->getCreatedAt(),
            SortParameter::UPDATED_AT => $cursor->getUpdatedAt(),
            SortParameter::POSITION => $cursor->getPosition()->value,
            default => null
        };
    }
}
