<?php

declare(strict_types=1);

namespace Affiliate\Infrastructure\Repositories\DoctrineOrm;

use Affiliate\Domain\Entities\AffiliateEntity;
use Affiliate\Domain\Exceptions\AffiliateNotFoundException;
use Affiliate\Domain\Repositories\AffiliateRepositoryInterface;
use Affiliate\Domain\ValueObjects\Code;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use DomainException;
use Override;
use Shared\Infrastructure\Repositories\DoctrineOrm\AbstractRepository;

class AffiliateRepository extends AbstractRepository implements
    AffiliateRepositoryInterface
{
    private const ENTITY_CLASS = AffiliateEntity::class;
    private const ALIAS = 'affiliate';

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, self::ENTITY_CLASS, self::ALIAS);
    }

    #[Override]
    public function ofCode(Code $code): AffiliateEntity
    {
        try {
            $object = $this->query()
                ->where(self::ALIAS . '.code.value = :code')
                ->setParameter(':code', $code->value)
                ->getQuery()
                ->getSingleResult();
        } catch (NoResultException $e) {
            throw new AffiliateNotFoundException($code);
        } catch (NonUniqueResultException $e) {
            throw new DomainException('More than one result found');
        }

        return $object;
    }

    public function getTotalClicks(): int
    {
        $value = $this->query()
            ->select('SUM(' . self::ALIAS . '.clickCount.value)')
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $value;
    }

    public function getTotalReferrals(): int
    {
        $value = $this->query()
            ->select('SUM(' . self::ALIAS . '.referralCount.value)')
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $value;
    }

    public function getTotalBalance(): int
    {
        $value = $this->query()
            ->select('SUM(' . self::ALIAS . '.balance.value)')
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $value;
    }

    public function getTotalPending(): int
    {
        $value = $this->query()
            ->select('SUM(' . self::ALIAS . '.pending.value)')
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $value;
    }

    public function getTotalWithdrawn(): int
    {
        $value = $this->query()
            ->select('SUM(' . self::ALIAS . '.withdrawn.value)')
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $value;
    }
}
