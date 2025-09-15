<?php

declare(strict_types=1);

namespace Affiliate\Application\CommandHandlers;

use Affiliate\Application\Commands\TrackAffiliateCommand;
use Affiliate\Domain\Entities\AffiliateEntity;
use Affiliate\Domain\Exceptions\AffiliateNotFoundException;
use Affiliate\Domain\Repositories\AffiliateRepositoryInterface;

class TrackAffiliateCommandHandler
{
    public function __construct(
        private AffiliateRepositoryInterface $repo,
    ) {}

    /**
     * @throws AffiliateNotFoundException
     */
    public function handle(TrackAffiliateCommand $cmd): AffiliateEntity
    {
        $affiliate = $this->repo->ofCode($cmd->code);

        if ($cmd->action === 'click') {
            $affiliate->click();
        } elseif ($cmd->action === 'referral') {
            $affiliate->referral();
        } elseif ($cmd->action === 'conversion') {
            $affiliate->conversion($cmd->amount);
        }

        return $affiliate;
    }
}
