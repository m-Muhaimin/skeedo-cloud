<?php

declare(strict_types=1);

namespace Assistant\Domain\Repositories;

use Assistant\Domain\Entities\AssistantEntity;
use Assistant\Domain\Exceptions\AssistantNotFoundException;
use Assistant\Domain\ValueObjects\SortParameter;
use Assistant\Domain\ValueObjects\Status;
use Shared\Domain\Repositories\RepositoryInterface;
use Shared\Domain\ValueObjects\Id;
use Shared\Domain\ValueObjects\SortDirection;
use Traversable;

/**
 * This interface defines the contract for an assistant repository.
 * It extends the RepositoryInterface and provides additional methods specific 
 * to assistant entities.
 */
interface AssistantRepositoryInterface extends RepositoryInterface
{
    /**
     * Add an assistant entity to the repository.
     *
     * @param AssistantEntity $assistant The assistant entity to add.
     * @return static Returns an instance of the repository with the added 
     * assistant entity.
     */
    public function add(AssistantEntity $assistant): static;

    /**
     * Remove an assistant entity from the repository.
     *
     * @param AssistantEntity $assistant The assistant entity to remove.
     * @return static Returns an instance of the repository without the removed 
     * assistant entity.
     */
    public function remove(AssistantEntity $assistant): static;

    /**
     * Retrieve an assistant entity by its ID.
     *
     * @param Id $id The ID of the assistant entity.
     * @return AssistantEntity|null Returns the assistant entity if found
     * @throws AssistantNotFoundException If the assistant entity is not found.
     */
    public function ofId(Id $id): AssistantEntity;

    /**
     * Filter the assistant entities in the repository by status.
     * 
     * @param Status $status
     * @return static
     */
    public function filterByStatus(Status $status): static;

    /**
     * @param Id|array<Id> $ids
     * @return static
     */
    public function filterById(Id|array $ids): static;

    /**
     * Filter the assistant entities in the repository by search query.
     *
     * @param string $status The status to filter by.
     * @return static Returns an instance of the repository with the filtered 
     * assistant entities.
     */
    public function search(string $query): static;

    /**
     * Sort the assistant entities in the repository.
     *
     * @param SortDirection $dir The sort direction.
     * @param SortParameter|null $param The sort parameter (optional).
     * @return static Returns an instance of the repository with the sorted 
     * assistant entities.
     */
    public function sort(
        SortDirection $dir,
        ?SortParameter $param = null
    ): static;

    /**
     * Retrieve assistant entities starting after a given assistant entity.
     *
     * @param AssistantEntity $cursor The assistant entity to start after.
     * @return Traversable<AssistantEntity> Returns a traversable collection of
     * assistant entities.
     */
    public function startingAfter(AssistantEntity $cursor): Traversable;

    /**
     * Retrieve assistant entities ending before a given assistant entity.
     *
     * @param AssistantEntity $cursor The assistant entity to end before.
     * @return Traversable<AssistantEntity> Returns a traversable collection of
     * assistant entities.
     */
    public function endingBefore(AssistantEntity $cursor): Traversable;
}
