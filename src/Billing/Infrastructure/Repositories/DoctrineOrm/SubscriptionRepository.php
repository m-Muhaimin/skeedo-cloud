<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Repositories\DoctrineOrm;

use Billing\Domain\Entities\PlanEntity;
use Billing\Domain\Entities\SubscriptionEntity;
use Billing\Domain\Exceptions\SubscriptionNotFoundException;
use Billing\Domain\Repositories\SubscriptionRepositoryInterface;
use Billing\Domain\ValueObjects\PaymentGateway;
use Billing\Domain\ValueObjects\ExternalId;
use Billing\Domain\ValueObjects\SubscriptionStatus;
use Billing\Domain\ValueObjects\SubscriptionSortParameter;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use DomainException;
use InvalidArgumentException;
use Iterator;
use LogicException;
use Psr\Cache\InvalidArgumentException as CacheInvalidArgumentException;
use RuntimeException;
use Shared\Domain\ValueObjects\Id;
use Shared\Domain\ValueObjects\SortDirection;
use Shared\Infrastructure\Repositories\DoctrineOrm\AbstractRepository;
use Workspace\Domain\Entities\WorkspaceEntity;

class SubscriptionRepository extends AbstractRepository implements
    SubscriptionRepositoryInterface
{
    private const ENTITY_CLASS = SubscriptionEntity::class;
    private const ALIAS = 'subscription';
    private ?SubscriptionSortParameter $sortParameter = null;

    /**
     * @param EntityManagerInterface $em 
     * @return void 
     * @throws InvalidArgumentException 
     * @throws RuntimeException 
     */
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, self::ENTITY_CLASS, self::ALIAS);
    }

    /**
     * @inheritDoc
     */
    public function ofId(Id $id): SubscriptionEntity
    {
        $object = $this->em->find(self::ENTITY_CLASS, $id);

        if ($object instanceof SubscriptionEntity) {
            return $object;
        }

        throw new SubscriptionNotFoundException($id);
    }

    /**
     * @inheritDoc
     * @throws DomainException 
     * @throws InvalidArgumentException 
     * @throws RuntimeException 
     */
    public function ofExteranlId(
        PaymentGateway $gateway,
        ExternalId $id
    ): SubscriptionEntity {
        try {
            $object = $this->query()
                ->where(self::ALIAS . '.paymentGateway.value = :payment_gateway')
                ->andWhere(self::ALIAS . '.externalId.value = :external_id')
                ->setParameter(':payment_gateway', $gateway->value)
                ->setParameter(':external_id', $id->value)
                ->getQuery()
                ->getSingleResult();
        } catch (NoResultException $e) {
            throw new SubscriptionNotFoundException($id, $gateway);
        } catch (NonUniqueResultException $e) {
            throw new DomainException('More than one result found');
        }

        return $object;
    }

    public function filterByStatus(SubscriptionStatus $status): static
    {
        if ($status == SubscriptionStatus::ENDED) {
            return $this->filter(static function (QueryBuilder $qb) {
                $qb->andWhere(self::ALIAS . '.endedAt IS NOT NULL');
            });
        }

        if ($status == SubscriptionStatus::CANCELED) {
            return $this->filter(static function (QueryBuilder $qb) {
                $qb->andWhere(self::ALIAS . '.canceledAt IS NOT NULL');
            });
        }

        if ($status == SubscriptionStatus::TRIALING) {
            return $this->filter(static function (QueryBuilder $qb) {
                $qb
                    ->andWhere(self::ALIAS . '.endedAt IS NULL')
                    ->andWhere(self::ALIAS . '.canceledAt IS NULL')
                    ->andWhere(self::ALIAS . '.trialPeriodDays.value IS NOT NULL')
                    ->andWhere(
                        $qb->expr()->gt(
                            self::ALIAS . '.trialPeriodDays.value',
                            0
                        )
                    )
                    ->andWhere(
                        $qb->expr()->lt(
                            'DATE_DIFF(CURRENT_DATE(), ' . self::ALIAS . '.createdAt)',
                            self::ALIAS . '.trialPeriodDays.value'
                        )
                    );
            });
        }

        if ($status == SubscriptionStatus::ACTIVE) {
            return $this->filter(static function (QueryBuilder $qb) {
                $qb->andWhere(self::ALIAS . '.endedAt IS NULL')
                    ->andWhere(self::ALIAS . '.canceledAt IS NULL');
            });
        }

        return $this;
    }

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

    public function sort(
        SortDirection $dir,
        ?SubscriptionSortParameter $sortParameter = null
    ): static {
        $cloned = $this->doSort($dir, $this->getSortKey($sortParameter));
        $cloned->sortParameter = $sortParameter;

        return $cloned;
    }

    /**
     * @inheritDoc
     * @throws LogicException 
     * @throws CacheInvalidArgumentException 
     * @throws ORMException 
     */
    public function startingAfter(SubscriptionEntity $cursor): Iterator
    {
        return $this->doStartingAfter(
            $cursor->getId(),
            $this->getCompareValue($cursor)
        );
    }

    /**
     * @inheritDoc
     */
    public function endingBefore(SubscriptionEntity $cursor): Iterator
    {
        return $this->doEndingBefore(
            $cursor->getId(),
            $this->getCompareValue($cursor)
        );
    }

    /**
     * @param SubscriptionEntity $cursor 
     * @return string 
     */
    private function getCompareValue(
        SubscriptionEntity $cursor
    ): null|int|string|DateTimeInterface {
        return match ($this->sortParameter) {
            SubscriptionSortParameter::ID => $cursor->getId()->getValue()->getBytes(),
            SubscriptionSortParameter::CREATED_AT => $cursor->getCreatedAt(),
            SubscriptionSortParameter::UPDATED_AT => $cursor->getUpdatedAt(),
            SubscriptionSortParameter::USAGE_COUNT => $cursor->getUsageCount()->value,
            default => null
        };
    }

    private function getSortKey(
        ?SubscriptionSortParameter $param
    ): ?string {
        return match ($param) {
            SubscriptionSortParameter::ID => 'id.value',
            SubscriptionSortParameter::CREATED_AT => 'createdAt',
            SubscriptionSortParameter::UPDATED_AT => 'updatedAt',
            SubscriptionSortParameter::USAGE_COUNT => 'usageCount.value',
            default => null
        };
    }
}
