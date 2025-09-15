<?php

declare(strict_types=1);

namespace Affiliate\Application\CommandHandlers;

use Affiliate\Application\Commands\UpdateAffiliateCommand;
use Affiliate\Domain\Entities\AffiliateEntity;

class UpdateAffiliateCommandHandler
{
    public function handle(UpdateAffiliateCommand $cmd): AffiliateEntity
    {
        $affiliate = $cmd->user->getAffiliate();

        if ($cmd->payoutMethod) {
            $affiliate->setPayoutMethod($cmd->payoutMethod);
        }

        if ($cmd->paypalEmail) {
            $affiliate->setPayPalEmail($cmd->paypalEmail);
        }

        if ($cmd->bankRequisites) {
            $affiliate->setBankRequisites($cmd->bankRequisites);
        }

        return $affiliate;
    }
}
