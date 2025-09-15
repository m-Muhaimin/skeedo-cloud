<?php

declare(strict_types=1);

namespace Affiliate\Domain\Repositories;

use Affiliate\Domain\Entities\PayoutEntity;
use Affiliate\Domain\Exceptions\PayoutNotFoundException;
use Affiliate\Domain\ValueObjects\SortParameter;
use Affiliate\Domain\ValueObjects\Status;
use Iterator;
use Shared\Domain\Repositories\RepositoryInterface;
use Shared\Domain\ValueObjects\Id;
use Shared\Domain\ValueObjects\SortDirection;
use User\Domain\Entities\UserEntity;

interface PayoutRepositoryInterface extends RepositoryInterface
{
    /**
     * Find payout entity by id
     *
     * @param Id $id
     * @return PayoutEntity
     * @throws PayoutNotFoundException
     */
    public function ofId(Id $id): PayoutEntity;

    /**
     * @param Status $status
     * @return static
     */
    public function filterByStatus(Status $status): static;

    /**
     * @param UserEntity $user
     * @return static
     */
    public function filterByUser(UserEntity $user): static;

    /**
     * @param SortDirection $dir 
     * @param null|SortParameter $sortParameter
     * @return static 
     */
    public function sort(
        SortDirection $dir,
        ?SortParameter $sortParameter = null
    ): static;

    /**
     * @param PayoutEntity $cursor 
     * @return Iterator<PayoutEntity> 
     */
    public function startingAfter(PayoutEntity $cursor): Iterator;

    /**
     * @param PayoutEntity $cursor 
     * @return Iterator<PayoutEntity> 
     */
    public function endingBefore(PayoutEntity $cursor): Iterator;
}
