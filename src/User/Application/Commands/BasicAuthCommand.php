<?php

declare(strict_types=1);

namespace User\Application\Commands;

use Shared\Infrastructure\CommandBus\Attributes\Handler;
use User\Application\CommandHandlers\BasicAuthCommandHandler;
use User\Domain\ValueObjects\Email;
use User\Domain\ValueObjects\Password;

#[Handler(BasicAuthCommandHandler::class)]
class BasicAuthCommand
{
    public Email $email;
    public Password $password;

    public function __construct(string $email, string $password)
    {
        $this->email = new Email($email);
        $this->password = new Password($password);
    }
}
