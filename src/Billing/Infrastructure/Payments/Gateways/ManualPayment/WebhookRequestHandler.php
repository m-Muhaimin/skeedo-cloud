<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Payments\Gateways\ManualPayment;

use Billing\Infrastructure\Payments\WebhookHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;

class WebhookRequestHandler implements WebhookHandlerInterface
{
    public function handle(ServerRequestInterface $request): void
    {
        // Do nothing
    }
}
