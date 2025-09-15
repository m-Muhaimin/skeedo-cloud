<?php

declare(strict_types=1);

namespace Affiliate\Infrastructure\Repositories\DoctrineOrm;

use Affiliate\Domain\Entities\PayoutEntity;
use Affiliate\Domain\Exceptions\PayoutNotFoundException;
use Affiliate\Domain\Repositories\PayoutRepositoryInterface;
use Affiliate\Domain\ValueObjects\SortParameter;
use Affiliate\Domain\ValueObjects\Status;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Iterator;
use Override;
use Shared\Domain\ValueObjects\Id;
use Shared\Domain\ValueObjects\SortDirection;
use Shared\Infrastructure\Repositories\DoctrineOrm\AbstractRepository;
use User\Domain\Entities\UserEntity;

class PayoutRepository extends AbstractRepository implements
    PayoutRepositoryInterface
{
    private const ENTITY_CLASS = PayoutEntity::class;
    private const ALIAS = 'payout';
    private ?SortParameter $sortParameter = null;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, self::ENTITY_CLASS, self::ALIAS);
    }

    #[Override]
    public function ofId(Id $id): PayoutEntity
    {
        $object = $this->em->find(self::ENTITY_CLASS, $id);

        if ($object instanceof PayoutEntity) {
            return $object;
        }

        throw new PayoutNotFoundException($id);
    }

    #[Override]
    public function filterByStatus(Status $status): static
    {
        return $this->filter(static function (QueryBuilder $qb) use ($status) {
            $qb->andWhere(self::ALIAS . '.status = :status')
                ->setParameter(':status', $status->value, Types::STRING);
        });
    }

    #[Override]
    public function filterByUser(UserEntity $user): static
    {
        $aff = $user->getAffiliate();
        $id = $aff->getId();

        return $this->filter(static function (QueryBuilder $qb) use ($id) {
            $qb
                ->andWhere(self::ALIAS . '.affiliate = :affiliate')
                ->setParameter(':affiliate', $id->getValue()->getBytes(), Types::STRING);
        });
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
    public function startingAfter(PayoutEntity $cursor): Iterator
    {
        return $this->doStartingAfter(
            $cursor->getId(),
            $this->getCompareValue($cursor)
        );
    }

    #[Override]
    public function endingBefore(PayoutEntity $cursor): Iterator
    {
        return $this->doEndingBefore(
            $cursor->getId(),
            $this->getCompareValue($cursor)
        );
    }

    /**
     * Returns the sort key based on the given SortParameter.
     *
     * @param SortParameter|null $param The SortParameter to determine the 
     * sort key.
     * @return string|null The sort key corresponding to the given SortParameter, 
     * or null if the SortParameter is not recognized.
     */
    private function getSortKey(
        ?SortParameter $param
    ): ?string {
        return match ($param) {
            SortParameter::ID => 'id.value',
            SortParameter::CREATED_AT => 'createdAt',
            SortParameter::UPDATED_AT => 'updatedAt',
            default => null
        };
    }

    /**
     * Get the compare value based on the sort parameter.
     *
     * @param PayoutEntity $cursor The payout entity to compare.
     * @return null|string|DateTimeInterface The compare value based on the 
     * sort parameter.
     */
    private function getCompareValue(
        PayoutEntity $cursor
    ): null|string|DateTimeInterface {
        return match ($this->sortParameter) {
            SortParameter::ID => $cursor->getId()->getValue()->getBytes(),
            SortParameter::CREATED_AT => $cursor->getCreatedAt(),
            SortParameter::UPDATED_AT => $cursor->getUpdatedAt(),
            default => null
        };
    }
}
