<?php

declare(strict_types=1);

namespace Billing\Application\CommandHandlers;

use Billing\Application\Commands\CancelOrderCommand;
use Billing\Domain\Entities\OrderEntity;
use Billing\Domain\Exceptions\OrderNotFoundException;
use Billing\Domain\Exceptions\InvalidOrderStateException;
use Billing\Domain\Repositories\OrderRepositoryInterface;
use Shared\Domain\ValueObjects\Id;

class CancelOrderCommandHandler
{
    public function __construct(
        private OrderRepositoryInterface $repo,
    ) {}

    /**
     * @throws OrderNotFoundException
     * @throws InvalidOrderStateException
     */
    public function handle(CancelOrderCommand $cmd): OrderEntity
    {
        $order = $cmd->order instanceof Id
            ? $this->repo->ofId($cmd->order) : $cmd->order;

        $order->cancel();

        return $order;
    }
}
