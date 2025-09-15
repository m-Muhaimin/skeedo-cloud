<?php

declare(strict_types=1);

namespace Ai\Domain\Repositories;

use Ai\Domain\Entities\AbstractLibraryItemEntity;
use Ai\Domain\Exceptions\LibraryItemNotFoundException;
use Ai\Domain\ValueObjects\ItemType;
use Ai\Domain\ValueObjects\Model;
use Ai\Domain\ValueObjects\State;
use Ai\Domain\ValueObjects\SortParameter;
use Ai\Domain\ValueObjects\Visibility;
use DateTimeInterface;
use Iterator;
use Shared\Domain\Repositories\RepositoryInterface;
use Shared\Domain\ValueObjects\Id;
use Shared\Domain\ValueObjects\SortDirection;
use User\Domain\Entities\UserEntity;
use Workspace\Domain\Entities\WorkspaceEntity;

interface LibraryItemRepositoryInterface extends RepositoryInterface
{
    /**
     * @param AbstractLibraryItemEntity $item
     * @return LibraryItemRepositoryInterface
     */
    public function add(AbstractLibraryItemEntity $item): self;

    /**
     * @param AbstractLibraryItemEntity $item
     * @return LibraryItemRepositoryInterface
     */
    public function remove(AbstractLibraryItemEntity $item): self;

    /**
     * @param Id $id
     * @return AbstractLibraryItemEntity
     * @throws LibraryItemNotFoundException
     */
    public function ofId(Id $id): AbstractLibraryItemEntity;

    /**
     * @param Id|UserEntity $user
     * @param Visibility|Id|WorkspaceEntity $visibility
     * @return static
     */
    public function filterByUser(
        Id|UserEntity $user,
        Visibility|Id|WorkspaceEntity $visibility = Visibility::PRIVATE
    ): static;

    /**
     * @param Id|WorkspaceEntity $workspace
     * @return static
     */
    public function filterByWorkspace(Id|WorkspaceEntity $workspace): static;

    /**
     * @param State ...$states
     * @return static
     */
    public function filterByState(State ...$states): static;

    /**
     * @param ItemType $type
     * @return static
     */
    public function filterByType(ItemType $type): static;

    /**
     * @param Model $model
     * @return static
     */
    public function filterByModel(Model $model): static;

    /**
     * @param DateTimeInterface $date
     * @return static
     */
    public function createdAfter(DateTimeInterface $date): static;

    /**
     * @param DateTimeInterface $date
     * @return static
     */
    public function createdBefore(DateTimeInterface $date): static;

    /**
     * @param string $terms
     * @return static
     */
    public function search(string $terms): static;

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
     * @param AbstractLibraryItemEntity $cursor
     * @return Iterator<AbstractLibraryItemEntity>
     */
    public function startingAfter(AbstractLibraryItemEntity $cursor): Iterator;

    /**
     * @param AbstractLibraryItemEntity $cursor
     * @return Iterator<AbstractLibraryItemEntity>
     */
    public function endingBefore(AbstractLibraryItemEntity $cursor): Iterator;
}
