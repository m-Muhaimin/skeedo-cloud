<?php

declare(strict_types=1);

namespace Workspace\Domain\Repositories;

use Iterator;
use Shared\Domain\Repositories\RepositoryInterface;
use Shared\Domain\ValueObjects\Id;
use Shared\Domain\ValueObjects\SortDirection;
use Workspace\Domain\Entities\WorkspaceEntity;
use Workspace\Domain\Exceptions\WorkspaceNotFoundException;
use Workspace\Domain\ValueObjects\SortParameter;


/**
 * This interface represents a workspace repository.
 */
interface WorkspaceRepositoryInterface extends RepositoryInterface
{
    /**
     * Add a new workspace entity to the repository.
     *
     * @param WorkspaceEntity $workspace The workspace entity to add.
     * @return static The updated workspace repository.
     */
    public function add(WorkspaceEntity $workspace): static;

    /**
     * Remove a workspace entity from the repository.
     *
     * @param WorkspaceEntity $workspace The workspace entity to remove.
     * @return static The updated workspace repository.
     */
    public function remove(WorkspaceEntity $workspace): static;

    /**
     * Find a workspace entity by its ID.
     *
     * @param Id $id The ID of the workspace entity to find.
     * @return WorkspaceEntity The found workspace entity.
     * @throws WorkspaceNotFoundException If the workspace entity is not found.
     */
    public function ofId(Id $id): WorkspaceEntity;

    /**
     * Sort the workspace entities in the repository.
     *
     * @param SortDirection $dir The sort direction.
     * @param null|SortParameter $sortParameter The sort parameter (optional).
     * @return static The sorted workspace repository.
     */
    public function sort(
        SortDirection $dir,
        ?SortParameter $sortParameter = null
    ): static;

    /**
     * Get an iterator of workspace entities starting after a given cursor.
     *
     * @param WorkspaceEntity $cursor The cursor entity.
     * @return Iterator<WorkspaceEntity> The iterator of workspace entities.
     */
    public function startingAfter(WorkspaceEntity $cursor): Iterator;

    /**
     * Get an iterator of workspace entities ending before a given cursor.
     *
     * @param WorkspaceEntity $cursor The cursor entity.
     * @return Iterator<WorkspaceEntity> The iterator of workspace entities.
     */
    public function endingBefore(WorkspaceEntity $cursor): Iterator;

    /**
     * Filter the workspace entities 
     * based on whether they have a subscription or not.
     *
     * @param bool $has Whether the workspace entities should have a 
     * subscription or not.
     * @return WorkspaceRepositoryInterface The filtered workspace repository.
     */
    public function hasSubscription($has = true): self;

    /**
     * Search for workspace entities based on the given terms.
     *
     * @param string $terms The search terms.
     * @return static The filtered workspace repository.
     */
    public function search(string $terms): static;
}
