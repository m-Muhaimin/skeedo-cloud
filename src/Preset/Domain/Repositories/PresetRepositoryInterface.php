<?php

declare(strict_types=1);

namespace Preset\Domain\Repositories;

use Category\Domain\Entities\CategoryEntity;
use Iterator;
use Preset\Domain\Entities\PresetEntity;
use Preset\Domain\Exceptions\PresetNotFoundException;
use Preset\Domain\ValueObjects\SortParameter;
use Preset\Domain\ValueObjects\Status;
use Preset\Domain\ValueObjects\Template;
use Preset\Domain\ValueObjects\Type;
use Shared\Domain\Repositories\RepositoryInterface;
use Shared\Domain\ValueObjects\Id;
use Shared\Domain\ValueObjects\SortDirection;

interface PresetRepositoryInterface extends RepositoryInterface
{
    /**
     * @param PresetEntity $preset 
     * @return static 
     */
    public function add(PresetEntity $preset): static;

    /**
     * @param PresetEntity $preset 
     * @return static 
     */
    public function remove(PresetEntity $preset): static;

    /**
     * @param Id $id 
     * @return PresetEntity 
     * @throws PresetNotFoundException
     */
    public function ofId(Id $id): PresetEntity;

    /**
     * @param Template $template 
     * @return null|PresetEntity 
     */
    public function ofTemplate(Template $template): ?PresetEntity;

    /**
     * @param Status $status 
     * @return static 
     */
    public function filterByStatus(Status $status): static;

    /**
     * @param Type $type 
     * @return static 
     */
    public function filterByType(Type $type): static;

    /**
     * @param bool $isLocked 
     * @return static 
     */
    public function filterByLock(bool $isLocked): static;

    /**
     * @param Id|CategoryEntity $category 
     * @return static 
     */
    public function filterByCategory(Id|CategoryEntity $category): static;

    /**
     * @param Id|array<Id> $ids
     * @return static
     */
    public function filterById(Id|array $ids): static;

    /**
     * @param string $query 
     * @return static 
     */
    public function search(string $query): static;

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
     * @param PresetEntity $cursor 
     * @return Iterator<PresetEntity>
     */
    public function startingAfter(PresetEntity $cursor): Iterator;

    /**
     * @param PresetEntity $cursor 
     * @return Iterator<PresetEntity>
     */
    public function endingBefore(PresetEntity $cursor): Iterator;
}
