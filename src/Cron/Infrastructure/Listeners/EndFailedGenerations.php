<?php

declare(strict_types=1);

namespace Cron\Infrastructure\Listeners;

use Ai\Application\Commands\ListLibraryItemsCommand;
use Ai\Domain\Entities\AbstractLibraryItemEntity;
use Ai\Domain\ValueObjects\State;
use Billing\Domain\ValueObjects\CreditCount;
use Cron\Domain\Events\CronEvent;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Shared\Infrastructure\CommandBus\Exception\NoHandlerFoundException;
use Traversable;

class EndFailedGenerations
{
    public function __construct(
        private Dispatcher $dispatcher
    ) {}

    /**
     * @throws NoHandlerFoundException
     */
    public function __invoke(CronEvent $event)
    {
        $cmd = new ListLibraryItemsCommand();
        $cmd->setState(State::DRAFT, State::QUEUED, State::PROCESSING);

        // Only end failed generations that are older than 1 hour
        $cmd->createdBefore(date('Y-m-d H:i:s', time() - 3600));
        $cmd->setLimit(20);

        /** @var Traversable<AbstractLibraryItemEntity> */
        $items = $this->dispatcher->dispatch($cmd);

        foreach ($items as $item) {
            $item->setState(State::FAILED);

            $reserved = new CreditCount(
                (float) ($item->getMeta('reserved_credit') ?: 0)
            );
            $item->getWorkspace()->unallocate($reserved);
        }
    }
}
