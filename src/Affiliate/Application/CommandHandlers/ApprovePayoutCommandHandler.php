<?php

declare(strict_types=1);

namespace Affiliate\Application\CommandHandlers;

use Affiliate\Application\Commands\ApprovePayoutCommand;
use Affiliate\Domain\Entities\PayoutEntity;
use Affiliate\Domain\Repositories\PayoutRepositoryInterface;
use Affiliate\Domain\Exceptions\PayoutNotFoundException;

class ApprovePayoutCommandHandler
{
    public function __construct(
        private PayoutRepositoryInterface $repo,
    ) {}

    /**
     * @throws PayoutNotFoundException
     */
    public function handle(ApprovePayoutCommand $cmd): PayoutEntity
    {
        $payout = $this->repo->ofId($cmd->id);
        $payout->approve();

        return $payout;
    }
}
