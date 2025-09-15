<?php

declare(strict_types=1);

namespace Billing\Application\CommandHandlers;

use Billing\Application\Commands\PayOrderCommand;
use Billing\Domain\Entities\OrderEntity;
use Billing\Domain\Exceptions\OrderNotFoundException;
use Billing\Domain\Exceptions\AlreadyFulfilledException;
use Billing\Domain\Exceptions\AlreadyPaidException;
use Billing\Domain\Repositories\OrderRepositoryInterface;
use Shared\Domain\ValueObjects\Id;

class PayOrderCommandHandler
{
    /**
     * @param OrderRepositoryInterface $repo
     * @return void
     */
    public function __construct(
        private OrderRepositoryInterface $repo,
    ) {
    }

    /**
     * @param PayOrderCommand $cmd
     * @return OrderEntity
     * @throws OrderNotFoundException
     * @throws AlreadyFulfilledException
     * @throws AlreadyPaidException
     */
    public function handle(PayOrderCommand $cmd): OrderEntity
    {
        $order = $cmd->order instanceof Id
            ? $this->repo->ofId($cmd->order) : $cmd->order;

        $order->pay($cmd->gateway, $cmd->externalId);

        return $order;
    }
}
