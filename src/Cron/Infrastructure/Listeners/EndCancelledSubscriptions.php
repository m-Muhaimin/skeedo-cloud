<?php

declare(strict_types=1);

namespace Cron\Infrastructure\Listeners;

use Billing\Application\Commands\EndSubscriptionCommand;
use Billing\Application\Commands\ListSubscriptionsCommand;
use Billing\Application\Commands\ReadSubscriptionCommand;
use Billing\Domain\Entities\SubscriptionEntity;
use Billing\Domain\Exceptions\PlanNotFoundException;
use Cron\Domain\Events\CronEvent;
use Easy\Container\Attributes\Inject;
use Option\Application\Commands\SaveOptionCommand;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Shared\Infrastructure\CommandBus\Exception\NoHandlerFoundException;
use Throwable;
use Traversable;

class EndCancelledSubscriptions
{
    public function __construct(
        private Dispatcher $dispatcher,

        #[Inject('option.cron.end_cancelled_subscriptions.cursor_id')]
        private ?string $cursorId = null,
    ) {
    }

    /**
     * @throws NoHandlerFoundException
     */
    public function __invoke(CronEvent $event)
    {
        $cursor = $this->getCursor();

        $cmd = new ListSubscriptionsCommand();

        if ($cursor) {
            $cmd->setCursor((string) $cursor->getId()->getValue());
        }

        $cmd->setLimit(20);

        /** @var Traversable<SubscriptionEntity> */
        $subs = $this->dispatcher->dispatch($cmd);

        $newCursor = null;
        foreach ($subs as $sub) {
            $newCursor = $sub;

            if (!$sub->isExpired()) {
                continue;
            }

            try {
                $cmd = new EndSubscriptionCommand($sub);
                $this->dispatcher->dispatch($cmd);
            } catch (PlanNotFoundException $th) {
                //throw $th;
            }
        }

        // Save new cursor
        $cmd = new SaveOptionCommand(
            'cron',
            json_encode([
                'end_cancelled_subscriptions' => [
                    'cursor_id' => $newCursor ? $newCursor->getId()->getValue() : ''
                ]
            ])
        );

        $this->dispatcher->dispatch($cmd);
    }

    private function getCursor(): ?SubscriptionEntity
    {
        if (!$this->cursorId) {
            return null;
        }

        try {
            $cmd = ReadSubscriptionCommand::createById($this->cursorId);
            $sub = $this->dispatcher->dispatch($cmd);
        } catch (Throwable $th) {
            return null;
        }

        return $sub;
    }
}
