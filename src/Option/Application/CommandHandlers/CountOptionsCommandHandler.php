<?php

declare(strict_types=1);

namespace Option\Application\CommandHandlers;

use Option\Application\Commands\CountOptionsCommand;
use Option\Domain\Repositories\OptionRepositoryInterface;

class CountOptionsCommandHandler
{
    public function __construct(
        private OptionRepositoryInterface $repo,
    ) {
    }

    public function handle(CountOptionsCommand $cmd): int
    {
        $options = $this->repo;
        return $options->count();
    }
}
