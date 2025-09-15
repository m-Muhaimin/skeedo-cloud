<?php

declare(strict_types=1);

namespace Billing\Application\Commands;

use Billing\Application\CommandHandlers\DeleteCouponCommandHandler;
use Billing\Domain\Entities\CouponEntity;
use Billing\Domain\ValueObjects\Code;
use Shared\Domain\ValueObjects\Id;
use Shared\Infrastructure\CommandBus\Attributes\Handler;
use Throwable;

#[Handler(DeleteCouponCommandHandler::class)]
class DeleteCouponCommand
{
    public Id|Code|CouponEntity $id;

    public function __construct(Id|Code|CouponEntity|string $id)
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
