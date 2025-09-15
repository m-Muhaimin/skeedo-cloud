<?php

declare(strict_types=1);

namespace Affiliate\Application\CommandHandlers;

use Affiliate\Application\Commands\ReadPayoutCommand;
use Affiliate\Domain\Entities\PayoutEntity;
use Affiliate\Domain\Repositories\PayoutRepositoryInterface;
use Affiliate\Domain\Exceptions\PayoutNotFoundException;

class ReadPayoutCommandHandler
{
    public function __construct(
        private PayoutRepositoryInterface $repo,
    ) {}

    /**
     * @throws PayoutNotFoundException
     */
    public function handle(ReadPayoutCommand $cmd): PayoutEntity
    {
        return $this->repo->ofId($cmd->id);
    }
}
