<?php

declare(strict_types=1);

namespace Workspace\Domain\Events;

use Easy\EventDispatcher\Attributes\Listener;
use Workspace\Infrastructure\Listeners\SendInvitationEmail;

#[Listener(SendInvitationEmail::class)]
class InvitationCreatedEvent extends AbstractInvitationEvent
{
}
