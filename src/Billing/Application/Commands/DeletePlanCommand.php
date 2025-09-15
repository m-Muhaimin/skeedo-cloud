<?php

declare(strict_types=1);

namespace Billing\Application\Commands;

use Billing\Application\CommandHandlers\DeletePlanCommandHandler;
use Billing\Domain\Entities\PlanEntity;
use Shared\Domain\ValueObjects\Id;
use Shared\Infrastructure\CommandBus\Attributes\Handler;

#[Handler(DeletePlanCommandHandler::class)]
class DeletePlanCommand
{
    public Id|PlanEntity $id;

    /**
     * @param string $id
     * @return void
     */
    public function __construct(string|Id|PlanEntity $id)
    {
        $this->id = is_string($id) ? new Id($id) : $id;
    }
}
