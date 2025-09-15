<?php

declare(strict_types=1);

namespace Stat\Domain\Repositories;

use DateTimeInterface;
use Shared\Domain\Repositories\RepositoryInterface;
use Shared\Domain\ValueObjects\Id;
use Shared\Domain\ValueObjects\SortDirection;
use Stat\Domain\Entities\AbstractStatEntity;
use Stat\Domain\ValueObjects\DatasetCategory;
use Stat\Domain\ValueObjects\StatType;
use Traversable;
use Workspace\Domain\Entities\WorkspaceEntity;

interface StatRepositoryInterface extends RepositoryInterface
{
    /**
     * Add a stat entity to the repository.
     * 
     * @param AbstractStatEntity $stat
     * @return static
     */
    public function add(AbstractStatEntity $stat): static;

    /**
     * Retrieve a stat entity by its ID.
     * 
     * @param Id $id
     * @return ?AbstractStatEntity
     */
    public function ofId(Id $id): ?AbstractStatEntity;

    /**
     * Filter the stat entity by type.
     * 
     * @param StatType $type
     * @return static
     */
    public function filterByType(StatType $type): static;

    /**
     * Filter the stat entity by workspace.
     * 
     * @param WorkspaceEntity $workspace
     * @return static
     */
    public function filterByWorkspace(WorkspaceEntity $workspace): static;

    /**
     * Filter the stat entity by year.
     * 
     * @param DateTimeInterface $date
     * @return static
     */
    public function filterByYear(DateTimeInterface $date): static;

    /**
     * Filter the stat entity by month.
     * 
     * @param DateTimeInterface $date
     * @return static
     */
    public function filterByMonth(DateTimeInterface $date): static;

    /**
     * Filter the stat entity by day.
     * 
     * @param DateTimeInterface $date
     * @return static
     */
    public function filterByDay(DateTimeInterface $date): static;

    /**
     * Filter the stat entity by start date.
     * 
     * @param DateTimeInterface $date
     * @return static
     */
    public function filterByStartDate(DateTimeInterface $date): static;

    /**
     * Filter the stat entity by end date.
     * 
     * @param DateTimeInterface $date
     * @return static
     */
    public function filterByEndDate(DateTimeInterface $date): static;

    /**
     * Get the total of the stat entity.
     * 
     * @return int
     */
    public function stat(): int;

    /**
     * Get the dataset for the stat entity.
     * 
     * @return Traversable<array{category:string,value:int}>
     */
    public function getDataset(
        DatasetCategory $type = DatasetCategory::DATE
    ): Traversable;

    /**
     * Sorts the categories in the repository.
     *
     * @param SortDirection $dir The sort direction.
     * @return static Returns the repository instance.
     */
    public function sort(
        SortDirection $dir
    ): static;

    /**
     * Retrieves categories starting after a given cursor.
     *
     * @param AbstractStatEntity $cursor The cursor stat.
     * @return Traversable<int, AbstractStatEntity> Returns a traversable 
     * collection of stats.
     */
    public function startingAfter(AbstractStatEntity $cursor): Traversable;

    /**
     * Retrieves categories ending before a given cursor.
     *
     * @param AbstractStatEntity $cursor The cursor stat.
     * @return Traversable<int, AbstractStatEntity> Returns a traversable 
     * collection of stats.
     */
    public function endingBefore(AbstractStatEntity $cursor): Traversable;
}
