<?php

declare(strict_types=1);

namespace Billing\Domain\Exceptions;

use Billing\Domain\ValueObjects\ExternalId;
use Billing\Domain\ValueObjects\PaymentGateway;
use Exception;
use Shared\Domain\ValueObjects\Id;
use Throwable;

class SubscriptionNotFoundException extends Exception
{
    public readonly ?Id $id;
    public readonly ?PaymentGateway $gateway;
    public readonly ?ExternalId $externalId;

    public function __construct(
        Id|ExternalId $id,
        ?PaymentGateway $gateway = null,
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        if ($id instanceof Id) {
            $message = sprintf(
                "Subscription with <%s> doesn't exists!",
                $id->getValue()
            );

            $this->id = $id;
            $this->externalId = null;
        } else {
            $message = sprintf(
                "Subscription with  <%s:%s> doesn't exists!",
                $id->value ?: '',
                $gateway->value ?: ''
            );

            $this->id = null;
            $this->externalId = $id;
        }

        $this->gateway = $gateway;
        parent::__construct($message, $code, $previous);
    }
}
