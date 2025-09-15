<?php

declare(strict_types=1);

namespace User\Domain\Events;

use Easy\EventDispatcher\Attributes\Listener;
use User\Infrastructure\Listeners\SendPasswordRecoveryEmail;

#[Listener(SendPasswordRecoveryEmail::class)]
class PasswordRecoveryCreatedEvent extends AbstractUserEvent
{
}
