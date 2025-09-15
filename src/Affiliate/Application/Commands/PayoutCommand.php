<?php

declare(strict_types=1);

namespace Affiliate\Application\Commands;

use Affiliate\Application\CommandHandlers\PayoutCommandHandler;
use Shared\Infrastructure\CommandBus\Attributes\Handler;
use User\Domain\Entities\UserEntity;

#[Handler(PayoutCommandHandler::class)]
class PayoutCommand
{
    public UserEntity $user;

    public function __construct(UserEntity $user)
    {
        $this->user = $user;
    }
}
