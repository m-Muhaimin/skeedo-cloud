<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Repositories\DoctrineOrm;

use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Billing\Domain\Entities\PlanSnapshotEntity;
use Billing\Domain\Exceptions\PlanSnapshotNotFoundException;
use Billing\Domain\Repositories\PlanSnapshotRepositoryInterface;
use Override;
use RuntimeException;
use Shared\Domain\ValueObjects\Id;
use Shared\Infrastructure\Repositories\DoctrineOrm\AbstractRepository;


class PlanSnapshotRepository extends AbstractRepository implements
    PlanSnapshotRepositoryInterface
{
    private const ENTITY_CLASS = PlanSnapshotEntity::class;
    private const ALIAS = 'plan_snapshot';

    /**
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, self::ENTITY_CLASS, self::ALIAS);
    }

    #[Override]
    public function ofId(Id $id): PlanSnapshotEntity
    {
        $object = $this->em->find(self::ENTITY_CLASS, $id);

        if ($object instanceof PlanSnapshotEntity) {
            return $object;
        }

        throw new PlanSnapshotNotFoundException($id);
    }
}
