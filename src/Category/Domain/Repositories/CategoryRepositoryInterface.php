<?php

declare(strict_types=1);

namespace Category\Domain\Repositories;

use Category\Domain\Entities\CategoryEntity;
use Category\Domain\Exceptions\CategoryNotFoundException;
use Category\Domain\ValueObjects\SortParameter;
use Shared\Domain\Repositories\RepositoryInterface;
use Shared\Domain\ValueObjects\Id;
use Shared\Domain\ValueObjects\SortDirection;
use Traversable;

/**
 * This interface represents a Category Repository.
 * It extends the RepositoryInterface.
 */
interface CategoryRepositoryInterface extends RepositoryInterface
{
    /**
     * Adds a category to the repository.
     *
     * @param CategoryEntity $category The category to add.
     * @return static Returns the repository instance.
     */
    public function add(CategoryEntity $category): static;

    /**
     * Removes a category from the repository.
     *
     * @param CategoryEntity $category The category to remove.
     * @return static Returns the repository instance.
     */
    public function remove(CategoryEntity $category): static;

    /**
     * Retrieves a category by its ID.
     *
     * @param Id $id The ID of the category.
     * @return CategoryEntity Returns the category entity
     * @throws CategoryNotFoundException If the category is not found.
     */
    public function ofId(Id $id): ?CategoryEntity;

    /**
     * Filter the category entities in the repository by search query.
     *
     * @param string $status The status to filter by.
     * @return static Returns an instance of the repository with the filtered 
     * assistant entities.
     */
    public function search(string $query): static;

    /**
     * Sorts the categories in the repository.
     *
     * @param SortDirection $dir The sort direction.
     * @param SortParameter|null $param The sort parameter (optional).
     * @return static Returns the repository instance.
     */
    public function sort(
        SortDirection $dir,
        ?SortParameter $param = null
    ): static;

    /**
     * Retrieves categories starting after a given cursor.
     *
     * @param CategoryEntity $cursor The cursor category.
     * @return Traversable<int, CategoryEntity> Returns a traversable 
     * collection of categories.
     */
    public function startingAfter(CategoryEntity $cursor): Traversable;

    /**
     * Retrieves categories ending before a given cursor.
     *
     * @param CategoryEntity $cursor The cursor category.
     * @return Traversable<int, CategoryEntity> Returns a traversable 
     * collection of categories.
     */
    public function endingBefore(CategoryEntity $cursor): Traversable;
}
