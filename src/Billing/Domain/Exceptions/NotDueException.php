<?php

declare(strict_types=1);

namespace Billing\Domain\Exceptions;

use Billing\Domain\Entities\SubscriptionEntity;
use Exception;
use Shared\Domain\ValueObjects\Id;
use Throwable;

class NotDueException extends Exception
{
    public function __construct(
        public readonly Id|SubscriptionEntity $subs,
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            sprintf(
                "Subscription with id <%s> is not due for renewal.",
                $subs instanceof SubscriptionEntity
                    ? $subs->getId()->getValue() : $subs->getValue()
            ),
            $code,
            $previous
        );
    }
}
