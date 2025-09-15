<?php

declare(strict_types=1);

namespace Affiliate\Application\Commands;

use Affiliate\Application\CommandHandlers\CountPayoutsCommandHandler;
use Affiliate\Domain\ValueObjects\Status;
use Shared\Domain\ValueObjects\Id;
use Shared\Infrastructure\CommandBus\Attributes\Handler;
use User\Domain\Entities\UserEntity;

#[Handler(CountPayoutsCommandHandler::class)]
class CountPayoutsCommand
{
    public ?Status $status = null;
    public null|Id|UserEntity $user = null;

    public function setStatus(string|Status $status): self
    {
        $this->status = is_string($status) ? Status::from($status) : $status;
        return $this;
    }

    public function setUser(string|Id|UserEntity $user): self
    {
        $this->user = is_string($user) ? new Id($user) : $user;
        return $this;
    }
}
