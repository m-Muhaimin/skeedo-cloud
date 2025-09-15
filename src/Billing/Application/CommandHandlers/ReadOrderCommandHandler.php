<?php

declare(strict_types=1);

namespace Billing\Application\CommandHandlers;

use Billing\Application\Commands\ReadOrderCommand;
use Billing\Domain\Entities\OrderEntity;
use Billing\Domain\Exceptions\OrderNotFoundException;
use Billing\Domain\Repositories\OrderRepositoryInterface;

class ReadOrderCommandHandler
{
    public function __construct(
        private OrderRepositoryInterface $repo,
    ) {
    }

    /**
     * @throws OrderNotFoundException
     */
    public function handle(ReadOrderCommand $cmd): OrderEntity
    {
        return $this->repo->ofId($cmd->id);
    }
}
