<?php

declare(strict_types=1);

namespace Billing\Application\CommandHandlers;

use Billing\Application\Commands\DeleteCouponCommand;
use Billing\Domain\Entities\CouponEntity;
use Billing\Domain\Events\CouponDeletedEvent;
use Billing\Domain\Exceptions\CouponNotFoundException;
use Billing\Domain\Repositories\CouponRepositoryInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

class DeleteCouponCommandHandler
{
    public function __construct(
        private CouponRepositoryInterface $repo,
        private EventDispatcherInterface $dispatcher,
    ) {}

    /**
     * @throws CouponNotFoundException
     */
    public function handle(DeleteCouponCommand $cmd): void
    {
        $coupon = $cmd->id instanceof CouponEntity
            ? $cmd->id
            : $this->repo->ofUniqueKey($cmd->id);

        $this->repo->remove($coupon);

        // Dispatch the plan deleted event
        $event = new CouponDeletedEvent($coupon);
        $this->dispatcher->dispatch($event);
    }
}
