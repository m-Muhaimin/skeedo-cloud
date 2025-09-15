<?php

declare(strict_types=1);

namespace Billing\Domain\Repositories;

use Billing\Domain\Entities\PlanEntity;
use Billing\Domain\Entities\SubscriptionEntity;
use Billing\Domain\Exceptions\SubscriptionNotFoundException;
use Billing\Domain\ValueObjects\ExternalId;
use Billing\Domain\ValueObjects\PaymentGateway;
use Billing\Domain\ValueObjects\SubscriptionSortParameter;
use Billing\Domain\ValueObjects\SubscriptionStatus;
use Iterator;
use Shared\Domain\Repositories\RepositoryInterface;
use Shared\Domain\ValueObjects\Id;
use Shared\Domain\ValueObjects\SortDirection;
use Workspace\Domain\Entities\WorkspaceEntity;

interface SubscriptionRepositoryInterface extends RepositoryInterface
{
    /**
     * Find entity by id
     *
     * @param Id $id
     * @return SubscriptionEntity
     * @throws SubscriptionNotFoundException
     */
    public function ofId(Id $id): SubscriptionEntity;

    /**
     * @param PaymentGateway $gateway 
     * @param ExternalId $id 
     * @return SubscriptionEntity 
     * @throws SubscriptionNotFoundException
     */
    public function ofExteranlId(
        PaymentGateway $gateway,
        ExternalId $id
    ): SubscriptionEntity;

    /**
     * @param SubscriptionStatus $status
     * @return static
     */
    public function filterByStatus(SubscriptionStatus $status): static;

    /**
     * @param Id|WorkspaceEntity $workspace
     * @return static
     */
    public function filterByWorkspace(Id|WorkspaceEntity $workspace): static;

    /**
     * @param Id|PlanEntity $plan
     * @return static
     */
    public function filterByPlan(Id|PlanEntity $plan): static;

    /**
     * @param SortDirection $dir
     * @param null|SubscriptionSortParameter $sortParameter
     * @return static
     */
    public function sort(
        SortDirection $dir,
        ?SubscriptionSortParameter $sortParameter = null
    ): static;

    /**
     * @param SubscriptionEntity $cursor
     * @return Iterator<SubscriptionEntity>
     */
    public function startingAfter(SubscriptionEntity $cursor): Iterator;

    /**
     * @param SubscriptionEntity $cursor
     * @return Iterator<SubscriptionEntity>
     */
    public function endingBefore(SubscriptionEntity $cursor): Iterator;
}
