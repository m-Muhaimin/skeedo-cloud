<?php

declare(strict_types=1);

namespace Billing\Application\Commands;

use Billing\Application\CommandHandlers\ReadCouponCommandHandler;
use Billing\Domain\ValueObjects\Code;
use Shared\Domain\ValueObjects\Id;
use Shared\Infrastructure\CommandBus\Attributes\Handler;
use Throwable;

#[Handler(ReadCouponCommandHandler::class)]
class ReadCouponCommand
{
    public Id|Code $id;

    public function __construct(Id|Code|string $id)
    {
        if (is_string($id)) {
            try {
                $this->id = new Id($id);
            } catch (Throwable $e) {
                $this->id = new Code($id);
            }
        } else {
            $this->id = $id;
        }
    }
}
