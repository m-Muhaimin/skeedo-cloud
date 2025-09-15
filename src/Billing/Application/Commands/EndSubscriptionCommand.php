<?php

declare(strict_types=1);

namespace Billing\Application\Commands;

use Billing\Application\CommandHandlers\EndSubscriptionCommandHandler;
use Shared\Infrastructure\CommandBus\Attributes\Handler;

#[Handler(EndSubscriptionCommandHandler::class)]
class EndSubscriptionCommand extends CancelSubscriptionCommand
{
}
