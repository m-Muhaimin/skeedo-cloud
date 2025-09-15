<?php

declare(strict_types=1);

namespace Affiliate\Domain\Repositories;

use Affiliate\Domain\Entities\AffiliateEntity;
use Affiliate\Domain\Exceptions\AffiliateNotFoundException;
use Affiliate\Domain\ValueObjects\Code;
use Shared\Domain\Repositories\RepositoryInterface;

interface AffiliateRepositoryInterface extends RepositoryInterface
{
    /**
     * Find payout entity by id
     *
     * @param Code $code
     * @return AffiliateEntity
     * @throws AffiliateNotFoundException
     */
    public function ofCode(Code $code): AffiliateEntity;

    /**
     * Get total clicks
     *
     * @return int
     */
    public function getTotalClicks(): int;

    /**
     * Get total referrals
     *
     * @return int
     */
    public function getTotalReferrals(): int;

    /**
     * Get total balance
     *
     * @return int
     */
    public function getTotalBalance(): int;

    /**
     * Get total pending
     *
     * @return int
     */
    public function getTotalPending(): int;

    /**
     * Get total withdrawn
     *
     * @return int
     */
    public function getTotalWithdrawn(): int;
}
