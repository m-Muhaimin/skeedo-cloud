<?php

declare(strict_types=1);

namespace User\Application\Commands;

use Shared\Domain\ValueObjects\Id;
use Shared\Infrastructure\CommandBus\Attributes\Handler;
use User\Application\CommandHandlers\UpdateUserCommandHandler;
use User\Domain\Entities\UserEntity;
use User\Domain\ValueObjects\Email;
use User\Domain\ValueObjects\FirstName;
use User\Domain\ValueObjects\Language;
use User\Domain\ValueObjects\LastName;
use User\Domain\ValueObjects\PhoneNumber;
use User\Domain\ValueObjects\Role;
use User\Domain\ValueObjects\Status;
use User\Domain\ValueObjects\WorkspaceCap;

#[Handler(UpdateUserCommandHandler::class)]
class UpdateUserCommand
{
    public Id|Email|UserEntity $id;
    public ?FirstName $firstName = null;
    public ?LastName $lastName = null;
    public ?PhoneNumber $phoneNumber = null;
    public ?Language $language = null;
    public ?Id $image = null;
    public bool $removeImage = false;

    public ?Role $role = null;
    public ?Status $status = null;
    public ?WorkspaceCap $workspaceCap = null;
    public ?array $preferences = null;

    public function __construct(string|Id|Email|UserEntity $id)
    {
        $this->id = is_string($id) ? new Id($id) : $id;
    }

    public function setFirstName(string $value): self
    {
        $this->firstName = new FirstName($value);
        return $this;
    }

    public function setLastName(string $value): self
    {
        $this->lastName = new LastName($value);

        return $this;
    }

    public function setPhoneNumber(?string $value): self
    {
        $this->phoneNumber = new PhoneNumber($value);
        return $this;
    }

    public function setLanguage(string $value): self
    {
        $this->language = new Language($value);

        return $this;
    }

    public function setRole(int $role): self
    {
        $this->role = Role::from($role);
        return $this;
    }

    public function setStatus(int $status): self
    {
        $this->status = Status::from($status);
        return $this;
    }

    public function setWorkspaceCap(?int $value): self
    {
        $this->workspaceCap = new WorkspaceCap($value);
        return $this;
    }
}
