<?php

declare(strict_types=1);

namespace Option\Application\CommandHandlers;

use Option\Application\Commands\ListOptionsCommand;
use Option\Domain\Entities\OptionEntity;
use Option\Domain\Repositories\OptionRepositoryInterface;
use Traversable;

class ListOptionsCommandHandler
{
    public function __construct(
        private OptionRepositoryInterface $repo
    ) {
    }

    /**
     * @return Traversable<OptionEntity>
     */
    public function handle(ListOptionsCommand $cmd): Traversable
    {
        $options = $this->repo;
        return $options;
    }
}
