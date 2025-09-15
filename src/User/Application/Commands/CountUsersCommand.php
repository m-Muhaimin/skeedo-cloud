<?php

declare(strict_types=1);

namespace User\Application\Commands;

use DateTime;
use DateTimeInterface;
use Shared\Domain\ValueObjects\CountryCode;
use Shared\Domain\ValueObjects\Id;
use Shared\Infrastructure\CommandBus\Attributes\Handler;
use User\Application\CommandHandlers\CountUsersCommandHandler;
use User\Domain\Entities\UserEntity;
use User\Domain\ValueObjects\IsEmailVerified;
use User\Domain\ValueObjects\Role;
use User\Domain\ValueObjects\Status;

#[Handler(CountUsersCommandHandler::class)]
class CountUsersCommand
{
    public ?Status $status = null;
    public ?Role $role = null;
    public ?CountryCode $countryCode = null;
    public ?IsEmailVerified $isEmailVerified = null;
    public ?DateTimeInterface $after = null;
    public ?DateTimeInterface $before = null;
    public null|Id|UserEntity $ref = null;

    /** Search terms/query */
    public ?string $query = null;

    public function setStatus(int $status): self
    {
        $this->status = Status::from($status);

        return $this;
    }

    public function setRole(int $role): self
    {
        $this->role = Role::from($role);

        return $this;
    }

    public function setCountryCode(string $countryCode): self
    {
        $this->countryCode = CountryCode::from($countryCode);

        return $this;
    }

    public function setIsEmailVerified(bool $isEmailVerified): self
    {
        $this->isEmailVerified = new IsEmailVerified($isEmailVerified);

        return $this;
    }

    public function setAfter(string $after): self
    {
        $date = new DateTime($after);

        // If the original string doesn't contain time information, set it to 00:00:00
        if (!preg_match('/\d{1,2}:\d{1,2}(:\d{1,2})?/', $after)) {
            $date->setTime(0, 0, 0);
        }

        $this->after = $date;

        return $this;
    }

    public function setBefore(string $before): self
    {
        $date = new DateTime($before);

        // If the original string doesn't contain time information, set it to 23:59:59
        if (!preg_match('/\d{1,2}:\d{1,2}(:\d{1,2})?/', $before)) {
            $date->setTime(23, 59, 59);
        }

        $this->before = $date;

        return $this;
    }

    public function setRef(string|Id|UserEntity $ref): self
    {
        $this->ref = is_string($ref) ? new Id($ref) : $ref;
        return $this;
    }
}
