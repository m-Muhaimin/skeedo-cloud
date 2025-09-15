<?php

declare(strict_types=1);

namespace Billing\Application\CommandHandlers;

use Billing\Application\Commands\ReadSubscriptionCommand;
use Billing\Domain\Entities\SubscriptionEntity;
use Billing\Domain\Exceptions\SubscriptionNotFoundException;
use Billing\Domain\Repositories\SubscriptionRepositoryInterface;
use InvalidArgumentException;

class ReadSubscriptionCommandHandler
{
    public function __construct(
        private SubscriptionRepositoryInterface $repo,
    ) {
    }

    /**
     * @throws SubscriptionNotFoundException
     * @throws InvalidArgumentException
     */
    public function handle(ReadSubscriptionCommand $cmd): SubscriptionEntity
    {
        if ($cmd->id) {
            return $this->repo->ofId($cmd->id);
        }

        if ($cmd->gateway && $cmd->externalId) {
            return $this->repo->ofExteranlId($cmd->gateway, $cmd->externalId);
        }

        throw new InvalidArgumentException('Invalid command: ' . $cmd::class);
    }
}
