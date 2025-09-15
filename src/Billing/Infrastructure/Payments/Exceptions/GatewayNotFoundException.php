<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Payments\Exceptions;

use Exception;
use Throwable;

class GatewayNotFoundException extends Exception
{
    /**
     * @param string $gateway 
     * @param int $code 
     * @param null|Throwable $previous 
     * @return void 
     */
    public function __construct(
        public readonly string $gateway,
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct("Gateway {$gateway} not found", $code, $previous);
    }
}
