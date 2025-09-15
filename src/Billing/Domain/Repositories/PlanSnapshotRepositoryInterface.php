<?php

declare(strict_types=1);

namespace Billing\Domain\Repositories;

use Billing\Domain\Entities\PlanSnapshotEntity;
use Billing\Domain\Exceptions\PlanSnapshotNotFoundException;
use Shared\Domain\Repositories\RepositoryInterface;
use Shared\Domain\ValueObjects\Id;

interface PlanSnapshotRepositoryInterface extends RepositoryInterface
{
    /**
     * @param Id $id
     * @return PlanSnapshotEntity
     * @throws PlanSnapshotNotFoundException
     */
    public function ofId(Id $id): PlanSnapshotEntity;
}
