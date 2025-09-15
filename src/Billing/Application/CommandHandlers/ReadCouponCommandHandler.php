<?php

declare(strict_types=1);

namespace Billing\Application\CommandHandlers;

use Billing\Application\Commands\ReadCouponCommand;
use Billing\Domain\Entities\CouponEntity;
use Billing\Domain\Exceptions\CouponNotFoundException;
use Billing\Domain\Repositories\CouponRepositoryInterface;

class ReadCouponCommandHandler
{
    public function __construct(
        private CouponRepositoryInterface $repo,
    ) {}

    /**
     * @throws CouponNotFoundException
     */
    public function handle(ReadCouponCommand $cmd): CouponEntity
    {
        return $this->repo->ofUniqueKey($cmd->id);
    }
}
