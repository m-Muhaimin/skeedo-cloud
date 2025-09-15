<?php

declare(strict_types=1);

namespace User\Application\Commands;

use Shared\Domain\ValueObjects\Id;
use Shared\Infrastructure\CommandBus\Attributes\Handler;
use User\Application\CommandHandlers\UpdatePasswordCommandHandler;
use User\Domain\Entities\UserEntity;
use User\Domain\ValueObjects\Email;
use User\Domain\ValueObjects\Password;

#[Handler(UpdatePasswordCommandHandler::class)]
class UpdatePasswordCommand
{
    public Id|Email|UserEntity $id;
    public Password $currentPassword;
    public Password $newPassword;

    public function __construct(
        string|Id|Email|UserEntity $id,
        string $currentPassword,
        string $newPassword
    ) {
        $this->id = is_string($id) ? new Id($id) : $id;
        $this->currentPassword = new Password($currentPassword);
        $this->newPassword = new Password($newPassword);
    }
}
