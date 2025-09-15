<?php

declare(strict_types=1);

namespace User\Domain\Events;

use Easy\EventDispatcher\Attributes\Listener;
use User\Infrastructure\Listeners\SendWelcomeEmail;

#[Listener(SendWelcomeEmail::class)]
class EmailVerifiedEvent extends UserUpdatedEvent
{
}
