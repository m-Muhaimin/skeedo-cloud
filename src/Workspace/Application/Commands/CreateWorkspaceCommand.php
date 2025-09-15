<?php

declare(strict_types=1);

namespace Workspace\Application\Commands;

use Shared\Infrastructure\CommandBus\Attributes\Handler;
use User\Domain\Entities\UserEntity;
use Workspace\Application\CommandHandlers\CreateWorkspaceCommandHandler;
use Workspace\Domain\ValueObjects\Address;
use Workspace\Domain\ValueObjects\Name;

#[Handler(CreateWorkspaceCommandHandler::class)]
class CreateWorkspaceCommand
{
    public Name $name;
    public ?Address $address = null;

    public function __construct(
        public UserEntity $user,
        string $name
    ) {
        $this->name = new Name($name);
    }

    public function setAddress(array $address): void
    {
        $this->address = new Address($address);
    }
}
