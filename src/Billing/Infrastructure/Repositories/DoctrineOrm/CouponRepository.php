<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Repositories\DoctrineOrm;

use Billing\Domain\Entities\CouponEntity;
use Billing\Domain\Entities\PlanEntity;
use Billing\Domain\Exceptions\CouponNotFoundException;
use Billing\Domain\Repositories\CouponRepositoryInterface;
use Billing\Domain\ValueObjects\BillingCycle;
use Billing\Domain\ValueObjects\Code;
use Billing\Domain\ValueObjects\CouponSortParameter;
use Billing\Domain\ValueObjects\DiscountType;
use Billing\Domain\ValueObjects\Status;
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
use Shared\Domain\ValueObjects\Id;
use Shared\Domain\ValueObjects\SortDirection;
use Shared\Infrastructure\Repositories\DoctrineOrm\AbstractRepository;

class CouponRepository extends AbstractRepository implements
    CouponRepositoryInterface
{
    private const ENTITY_CLASS = CouponEntity::class;
    private const ALIAS = 'coupon';

    private ?CouponSortParameter $sortParameter = null;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, self::ENTITY_CLASS, self::ALIAS);
    }


    #[Override]
    public function add(CouponEntity $coupon): static
    {
        $this->em->persist($coupon);
        return $this;
    }

    #[Override]
    public function remove(CouponEntity $coupon): static
    {
        $count = $coupon->getRedemptionCount();

        if ($count > 0) {
            $coupon->markAsDeleted();
        } else {
            $this->em->remove($coupon);
        }

        return $this;
    }

    #[Override]
    public function ofId(Id $id): CouponEntity
    {
        $object = $this->em->find(self::ENTITY_CLASS, $id);

        if (
            $object instanceof CouponEntity
            && is_null($object->getDeletedAt())
        ) {
            return $object;
        }

        throw new CouponNotFoundException($id);
    }

    #[Override]
    public function ofCode(Code $code): CouponEntity
    {
        try {
            $object = $this->query()
                ->where(self::ALIAS . '.code.value = :code')
                ->setParameter(':code', $code->value)
                ->getQuery()
                ->getSingleResult();
        } catch (NoResultException $e) {
            throw new CouponNotFoundException($code);
        } catch (NonUniqueResultException $e) {
            throw new DomainException('More than one result found');
        }

        if (
            $object instanceof CouponEntity
            && is_null($object->getDeletedAt())
        ) {
            return $object;
        }

        throw new CouponNotFoundException($code);
    }

    #[Override]
    public function ofUniqueKey(Code|Id $key): CouponEntity
    {
        return match (true) {
            $key instanceof Code => $this->ofCode($key),
            $key instanceof Id => $this->ofId($key),
            default => throw new InvalidArgumentException('Invalid key type')
        };
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
    public function filterByBillingCycle(BillingCycle $billingCycle): static
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($billingCycle) {
                $qb->andWhere(self::ALIAS . '.billingCycle = :billingCycle')
                    ->setParameter(
                        ':billingCycle',
                        $billingCycle->value,
                        Types::STRING
                    );
            }
        );
    }

    #[Override]
    public function filterByDiscountType(DiscountType $discountType): static
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($discountType) {
                $qb->andWhere(self::ALIAS . '.discountType = :discountType')
                    ->setParameter(
                        ':discountType',
                        $discountType->value,
                        Types::STRING
                    );
            }
        );
    }

    #[Override]
    public function filterByPlan(Id|PlanEntity $plan): static
    {
        $id = $plan instanceof PlanEntity
            ? $plan->getId()
            : $plan;

        return $this->filter(
            static function (QueryBuilder $qb) use ($id) {
                $qb->andWhere(self::ALIAS . '.plan = :plan')
                    ->setParameter(
                        ':plan',
                        $id->getValue()->getBytes(),
                        Types::STRING
                    );
            }
        );
    }

    #[Override]
    public function search(string $terms): static
    {
        return $this->filter(
            static function (QueryBuilder $qb) use ($terms) {
                $qb->andWhere(
                    $qb->expr()->orX(
                        self::ALIAS . '.title.value LIKE :search',
                        self::ALIAS . '.code.value LIKE :search'
                    )
                )->setParameter('search', $terms . '%');
            }
        );
    }

    #[Override]
    public function sort(
        SortDirection $dir,
        ?CouponSortParameter $sortParameter = null
    ): static {
        $cloned = $this->doSort($dir, $this->getSortKey($sortParameter));
        $cloned->sortParameter = $sortParameter;

        return $cloned;
    }

    #[Override]
    public function startingAfter(CouponEntity $cursor): Iterator
    {
        return $this->doStartingAfter(
            $cursor->getId(),
            $this->getCompareValue($cursor)
        );
    }

    #[Override]
    public function endingBefore(CouponEntity $cursor): Iterator
    {
        return $this->doEndingBefore(
            $cursor->getId(),
            $this->getCompareValue($cursor)
        );
    }

    private function getSortKey(?CouponSortParameter $param): ?string
    {
        return match ($param) {
            CouponSortParameter::ID => 'id.value',
            CouponSortParameter::CREATED_AT => 'createdAt',
            CouponSortParameter::UPDATED_AT => 'updatedAt',
            CouponSortParameter::TITLE => 'title.value',
            default => null,
        };
    }

    private function getCompareValue(CouponEntity $cursor): null|string|DateTimeInterface
    {
        return match ($this->sortParameter) {
            CouponSortParameter::ID => $cursor->getId()->getValue()->getBytes(),
            CouponSortParameter::CREATED_AT => $cursor->getCreatedAt(),
            CouponSortParameter::UPDATED_AT => $cursor->getUpdatedAt(),
            CouponSortParameter::TITLE => $cursor->getTitle()->value,
            default => null,
        };
    }
}
