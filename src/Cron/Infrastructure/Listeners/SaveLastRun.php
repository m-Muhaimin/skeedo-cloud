<?php

declare(strict_types=1);

namespace Cron\Infrastructure\Listeners;

use Cron\Domain\Events\CronEvent;
use Option\Application\Commands\SaveOptionCommand;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Shared\Infrastructure\CommandBus\Exception\NoHandlerFoundException;

class SaveLastRun
{
    public function __construct(
        private Dispatcher $dispatcher,
    ) {}

    /**
     * @throws NoHandlerFoundException
     */
    public function __invoke(CronEvent $event)
    {
        // Save last run
        $cmd = new SaveOptionCommand(
            'cron',
            json_encode([
                'last_run' => time()
            ])
        );

        $this->dispatcher->dispatch($cmd);
    }
}
