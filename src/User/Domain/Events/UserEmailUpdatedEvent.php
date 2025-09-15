<?php

declare(strict_types=1);

namespace User\Domain\Events;

use Easy\EventDispatcher\Attributes\Listener;
use User\Infrastructure\Listeners\SendVerificationEmail;

#[Listener(SendVerificationEmail::class)]
class UserEmailUpdatedEvent extends UserUpdatedEvent
{
}
