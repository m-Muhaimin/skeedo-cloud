<?php

declare(strict_types=1);

namespace Billing\Domain\Exceptions;

use Billing\Domain\Entities\PlanSnapshotEntity;
use Exception;
use Shared\Domain\ValueObjects\Id;
use Throwable;


class NotSubscriptionPlanException extends Exception
{
    public function __construct(
        public readonly Id|PlanSnapshotEntity $plan,
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            sprintf(
                "Plan with id <%s> is not a subscription plan.",
                $plan instanceof PlanSnapshotEntity
                    ? $plan->getId()->getValue() : $plan->getValue()
            ),
            $code,
            $previous
        );
    }
}
