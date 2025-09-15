<?php

declare(strict_types=1);

namespace Cron\Infrastructure\Listeners;

use Billing\Application\Commands\RenewSubscriptionCommand;
use Cron\Domain\Events\CronEvent;
use Easy\Container\Attributes\Inject;
use Option\Application\Commands\SaveOptionCommand;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Shared\Infrastructure\CommandBus\Exception\NoHandlerFoundException;
use Throwable;
use Traversable;
use Workspace\Application\Commands\ListWorkspacesCommand;
use Workspace\Application\Commands\ReadWorkspaceCommand;
use Workspace\Domain\Entities\WorkspaceEntity;

class RenewSubscriptions
{
    public function __construct(
        private Dispatcher $dispatcher,

        #[Inject('option.cron.renew_subscriptions.cursor_id')]
        private ?string $cursorId = null,
    ) {
    }

    /**
     * @throws NoHandlerFoundException
     */
    public function __invoke(CronEvent $event)
    {
        $cursor = $this->getCursor();

        $cmd = new ListWorkspacesCommand();
        $cmd->hasSubscription = true;
        $cmd->setOrderBy('id', 'asc');

        if ($cursor) {
            $cmd->setCursor((string) $cursor->getId()->getValue());
        }

        $cmd->setLimit(20);

        /** @var Traversable<WorkspaceEntity> */
        $workspaces = $this->dispatcher->dispatch($cmd);

        $newCursor = null;
        foreach ($workspaces as $ws) {
            $newCursor = $ws;

            $cmd = new RenewSubscriptionCommand($ws->getSubscription());
            $this->dispatcher->dispatch($cmd);
        }

        // Save new cursor
        $cmd = new SaveOptionCommand(
            'cron',
            json_encode([
                'renew_subscriptions' => [
                    'cursor_id' => $newCursor ? $newCursor->getId()->getValue() : ''
                ]
            ])
        );

        $this->dispatcher->dispatch($cmd);
    }

    /** @return null|WorkspaceEntity */
    private function getCursor(): ?WorkspaceEntity
    {
        if (!$this->cursorId) {
            return null;
        }

        try {
            $cmd = new ReadWorkspaceCommand($this->cursorId);

            /** @var WorkspaceEntity */
            $ws = $this->dispatcher->dispatch($cmd);
        } catch (Throwable $th) {
            return null;
        }

        return $ws;
    }
}
