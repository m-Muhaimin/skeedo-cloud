<?php

declare(strict_types=1);

namespace Option\Domain\Repositories;

use Option\Domain\Entities\OptionEntity;
use Option\Domain\Exceptions\KeyTakenException;
use Option\Domain\Exceptions\OptionNotFoundException;
use Option\Domain\ValueObjects\Key;
use Shared\Domain\Repositories\RepositoryInterface;
use Shared\Domain\ValueObjects\Id;

interface OptionRepositoryInterface extends RepositoryInterface
{
    /**
     * Add new entityt to the repository
     *
     * @param OptionEntity $option
     * @return OptionRepositoryInterface
     * @throws KeyTakenException
     */
    public function add(OptionEntity $option): self;

    /**
     * Remove the entity from the repository
     *
     * @param OptionEntity $option
     * @return OptionRepositoryInterface
     */
    public function remove(OptionEntity $option): self;

    /**
     * Find entity by id
     *
     * @param Id $id
     * @return OptionEntity
     * @throws OptionNotFoundException
     */
    public function ofId(Id $id): OptionEntity;

    /**
     * Find entity by key
     *
     * @param Key $key
     * @return OptionEntity
     * @throws OptionNotFoundException
     */
    public function ofKey(Key $key): OptionEntity;
}
