<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Payments\Gateways\Stripe;

use Easy\Container\Attributes\Inject;
use Stripe\StripeClient;

class Client extends StripeClient
{
    public function __construct(
        #[Inject('option.stripe.secret_key')]
        private ?string $secretKey = null
    ) {
        if ($this->secretKey) {
            parent::__construct($this->secretKey);
        }
    }
}
