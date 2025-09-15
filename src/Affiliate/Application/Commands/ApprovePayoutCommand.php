<?php

declare(strict_types=1);

namespace Affiliate\Application\Commands;

use Affiliate\Application\CommandHandlers\ApprovePayoutCommandHandler;
use Shared\Domain\ValueObjects\Id;
use Shared\Infrastructure\CommandBus\Attributes\Handler;

#[Handler(ApprovePayoutCommandHandler::class)]
class ApprovePayoutCommand
{
    public Id $id;

    public function __construct(Id|string $id)
    {
        $this->id = is_string($id)
            ? new Id($id) : $id;
    }
}
