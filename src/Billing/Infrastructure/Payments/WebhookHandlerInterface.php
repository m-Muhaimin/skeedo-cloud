<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Payments;

use Billing\Infrastructure\Payments\Exceptions\WebhookException;
use Psr\Http\Message\ServerRequestInterface;

interface WebhookHandlerInterface
{
    /**
     * Handles the webhook request.
     *
     * @param ServerRequestInterface $request The request to handle.
     * @return void
     * @throws WebhookException
     */
    public function handle(ServerRequestInterface $request): void;
}
