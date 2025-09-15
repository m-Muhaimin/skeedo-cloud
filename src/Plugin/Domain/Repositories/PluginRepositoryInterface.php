<?php

declare(strict_types=1);

namespace Plugin\Domain\Repositories;

use Plugin\Domain\Exceptions\PluginNotFoundException;
use Plugin\Domain\PluginWrapper;
use Plugin\Domain\ValueObjects\Name;
use Plugin\Domain\ValueObjects\Status;
use Plugin\Domain\ValueObjects\Type;
use Shared\Domain\Repositories\RepositoryInterface;

interface PluginRepositoryInterface extends RepositoryInterface
{
    /**
     * @param PluginWrapper $wrapper
     * @return void
     */
    public function add(PluginWrapper $wrapper): void;

    /**
     * @param PluginWrapper $wrapper
     * @return void
     */
    public function remove(PluginWrapper $wrapper): void;

    /**
     * @param Status $status
     * @return PluginRepositoryInterface
     */
    public function filterByStatus(Status $status): PluginRepositoryInterface;

    /**
     * @param Type $type
     * @return PluginRepositoryInterface
     */
    public function filterByType(Type $type): PluginRepositoryInterface;

    /**
     * @param Name $name
     * @return PluginWrapper
     * @throws PluginNotFoundException
     */
    public function ofName(Name $name): PluginWrapper;

    /**
     * @param string $terms
     * @return PluginRepositoryInterface
     */
    public function search(string $terms): self;
}
