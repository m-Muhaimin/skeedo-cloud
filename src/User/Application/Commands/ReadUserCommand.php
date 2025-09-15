<?php

declare(strict_types=1);

namespace User\Application\Commands;

use Shared\Domain\ValueObjects\Id;
use Shared\Infrastructure\CommandBus\Attributes\Handler;
use User\Application\CommandHandlers\ReadUserCommandHandler;
use User\Domain\ValueObjects\ApiKey;
use User\Domain\ValueObjects\Email;

#[Handler(ReadUserCommandHandler::class)]
class ReadUserCommand
{
    public Id|Email|ApiKey $id;

    public function __construct(string|Id|Email|ApiKey $id)
    {
        if (is_string($id)) {
            $this->id = filter_var($id, FILTER_VALIDATE_EMAIL)
                ? new Email($id)
                : new Id($id);
        } else {
            $this->id = $id;
        }
    }
}
