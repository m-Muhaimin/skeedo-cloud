<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Repositories\DoctrineOrm;

use Billing\Domain\Entities\CouponEntity;
use Billing\Domain\Entities\OrderEntity;
use Billing\Domain\Entities\PlanEntity;
use Billing\Domain\Entities\PlanSnapshotEntity;
use Billing\Domain\Exceptions\OrderNotFoundException;
use Billing\Domain\Repositories\OrderRepositoryInterface;
use Billing\Domain\ValueObjects\BillingCycle;
use Billing\Domain\ValueObjects\OrderStatus;
use Billing\Domain\ValueObjects\OrderSortParameter;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Iterator;
use Override;
use Ramsey\Uuid\Uuid;
use Shared\Domain\ValueObjects\Id;
use Shared\Domain\ValueObjects\SortDirection;
use Shared\Infrastructure\Repositories\DoctrineOrm\AbstractRepository;
use Throwable;
use Workspace\Domain\Entities\WorkspaceEntity;

class OrderRepository extends AbstractRepository implements
    OrderRepositoryInterface
{
    private const ENTITY_CLASS = OrderEntity::class;
    private const ALIAS = 'o';
    private ?OrderSortParameter $sortParameter = null;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, self::ENTITY_CLASS, self::ALIAS);
    }

    #[Override]
    public function add(OrderEntity $order): OrderRepositoryInterface
    {
        $this->em->persist($order);
        return $this;
    }

    #[Override]
    public function ofId(Id $id): OrderEntity
    {
        $object = $this->em->find(self::ENTITY_CLASS, $id);

        if ($object instanceof OrderEntity) {
            return $object;
        }

        throw new OrderNotFoundException($id);
    }

    #[Override]
    public function filterByStatus(OrderStatus $status): static
    {
        return $this->filter(static function (QueryBuilder $qb) use ($status) {
            $qb->andWhere(self::ALIAS . '.status = :status')
                ->setParameter(':status', $status->value, Types::STRING);
        });
    }

    #[Override]
    public function filterByWorkspace(Id|WorkspaceEntity $workspace): static
    {
        $id = $workspace instanceof WorkspaceEntity
            ? $workspace->getId()
            : $workspace;

        return $this->filter(static function (QueryBuilder $qb) use ($id) {
            $qb->andWhere(self::ALIAS . '.workspace = :workspace')
                ->setParameter(
                    ':workspace',
                    $id->getValue()->getBytes(),
                    Types::STRING
                );
        });
    }

    #[Override]
    public function filterByPlan(Id|PlanEntity $plan): static
    {
        $id = $plan instanceof PlanEntity
            ? $plan->getId()
            : $plan;

        return $this->filter(static function (QueryBuilder $qb) use ($id) {
            $qb
                ->leftJoin(self::ALIAS . '.plan', 'snapshot')
                // ->leftJoin('snapshot.plan', 'plan')
                ->andWhere('snapshot.plan = :plan')
                ->setParameter(
                    ':plan',
                    $id->getValue()->getBytes(),
                    Types::STRING
                );
        });
    }

    #[Override]
    public function filterByCoupon(Id|CouponEntity $coupon): static
    {
        $id = $coupon instanceof CouponEntity
            ? $coupon->getId()
            : $coupon;

        return $this->filter(static function (QueryBuilder $qb) use ($id) {
            $qb->andWhere(self::ALIAS . '.coupon = :coupon')
                ->setParameter(
                    ':coupon',
                    $id->getValue()->getBytes(),
                    Types::STRING
                );
        });
    }

    #[Override]
    public function filterByPlanSnapshot(
        Id|PlanSnapshotEntity $snapshot
    ): static {
        $id = $snapshot instanceof PlanSnapshotEntity
            ? $snapshot->getId()
            : $snapshot;

        return $this->filter(static function (QueryBuilder $qb) use ($id) {
            $qb
                ->andWhere(self::ALIAS . '.plan = :psnapshot')
                ->setParameter(
                    ':psnapshot',
                    $id->getValue()->getBytes(),
                    Types::STRING
                );
        });
    }

    #[Override]
    public function filterByBillingCycle(BillingCycle $billingCycle): static
    {
        return $this->filter(static function (QueryBuilder $qb) use ($billingCycle) {
            $qb
                ->leftJoin(self::ALIAS . '.plan', 'snapshot')
                ->andWhere('snapshot.billingCycle = :billingCycle')
                ->setParameter(
                    ':billingCycle',
                    $billingCycle->value,
                    Types::STRING
                );
        });
    }

    #[Override]
    public function search(string $terms): static
    {
        return $this->filter(static function (QueryBuilder $qb) use ($terms) {
            try {
                $id = Uuid::fromString($terms);

                $qb->andWhere(
                    $qb->expr()->orX(
                        self::ALIAS . '.id.value = :search_id',
                        self::ALIAS . '.externalId.value = :search_external'
                    )
                )
                    ->setParameter('search_id', $id->getBytes(), Types::STRING)
                    ->setParameter('search_external', $terms, Types::STRING);
            } catch (Throwable) {
                $qb->andWhere(self::ALIAS . '.externalId.value LIKE :search')
                    ->setParameter('search', $terms . '%', Types::STRING);
            }
        });
    }

    #[Override]
    public function sort(
        SortDirection $dir,
        ?OrderSortParameter $sortParameter = null
    ): static {
        $cloned = $this->doSort($dir, $this->getSortKey($sortParameter));
        $cloned->sortParameter = $sortParameter;

        return $cloned;
    }

    #[Override]
    public function startingAfter(OrderEntity $cursor): Iterator
    {
        return $this->doStartingAfter(
            $cursor->getId(),
            $this->getCompareValue($cursor)
        );
    }

    #[Override]
    public function endingBefore(OrderEntity $cursor): Iterator
    {
        return $this->doEndingBefore(
            $cursor->getId(),
            $this->getCompareValue($cursor)
        );
    }

    private function getCompareValue(
        OrderEntity $cursor
    ): null|string|DateTimeInterface {
        return match ($this->sortParameter) {
            OrderSortParameter::ID => $cursor->getId()->getValue()->getBytes(),
            OrderSortParameter::CREATED_AT => $cursor->getCreatedAt(),
            OrderSortParameter::UPDATED_AT => $cursor->getUpdatedAt(),
            default => null
        };
    }

    private function getSortKey(
        ?OrderSortParameter $param
    ): ?string {
        return match ($param) {
            OrderSortParameter::ID => 'id.value',
            OrderSortParameter::CREATED_AT => 'createdAt',
            OrderSortParameter::UPDATED_AT => 'updatedAt',
            default => null
        };
    }
}
