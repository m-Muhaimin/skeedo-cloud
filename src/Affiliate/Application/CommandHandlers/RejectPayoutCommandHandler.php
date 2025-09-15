<?php

declare(strict_types=1);

namespace Affiliate\Application\CommandHandlers;

use Affiliate\Application\Commands\RejectPayoutCommand;
use Affiliate\Domain\Entities\PayoutEntity;
use Affiliate\Domain\Repositories\PayoutRepositoryInterface;
use Affiliate\Domain\Exceptions\PayoutNotFoundException;

class RejectPayoutCommandHandler
{
    public function __construct(
        private PayoutRepositoryInterface $repo,
    ) {}

    /**
     * @throws PayoutNotFoundException
     */
    public function handle(RejectPayoutCommand $cmd): PayoutEntity
    {
        $payout = $this->repo->ofId($cmd->id);
        $payout->reject();

        return $payout;
    }
}
