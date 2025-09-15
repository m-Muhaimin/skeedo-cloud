<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Payments\Gateways\PayPal;

use Billing\Application\Commands\CancelSubscriptionCommand;
use Billing\Application\Commands\ReadSubscriptionCommand;
use Billing\Domain\Exceptions\PlanNotFoundException;
use Billing\Domain\Exceptions\SubscriptionNotFoundException;
use Billing\Infrastructure\Payments\WebhookHandlerInterface;
use Easy\Container\Attributes\Inject;
use Easy\Http\Message\StatusCode;
use Presentation\Exceptions\HttpException;
use Psr\Http\Message\ServerRequestInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;

class WebhookRequestHandler implements WebhookHandlerInterface
{
    public function __construct(
        private Dispatcher $dispatcher,
        private Client $client,

        #[Inject('option.paypal.is_enabled')]
        private bool $isEnabled = false,

        #[Inject('option.paypal.webhook_id')]
        private ?string $webhookId = null
    ) {}

    public function handle(ServerRequestInterface $request): void
    {
        $this->validateRequest($request);


        $request->getBody()->rewind();
        $event = json_decode($request->getBody()->getContents());

        $resource = $event->resource;
        $type = $event->event_type;

        switch ($type) {
            case 'INVOICING.INVOICE.CANCELLED':
            case 'INVOICING.INVOICE.CREATED':
            case 'INVOICING.INVOICE.PAID':
            case 'INVOICING.INVOICE.REFUNDED':
            case 'INVOICING.INVOICE.SCHEDULED':
            case 'INVOICING.INVOICE.UPDATED':
                $this->saveInvoice($resource);
                break;

            case 'BILLING.SUBSCRIPTION.CANCELLED':
                $this->cancelSubscription($resource);
                break;

            default:
                break;
        }
    }

    private  function validateRequest(ServerRequestInterface $request): void
    {
        if (!$this->isEnabled) {
            throw new HttpException(
                'PayPal is not enabled',
                StatusCode::BAD_REQUEST
            );
        }

        if (!$this->webhookId) {
            throw new HttpException(
                'Missing PayPal webhook id',
                StatusCode::INTERNAL_SERVER_ERROR
            );
        }

        // Check headers
        $headers = [
            'PAYPAL-AUTH-ALGO',
            'PAYPAL-CERT-URL',
            'PAYPAL-TRANSMISSION-ID',
            'PAYPAL-TRANSMISSION-TIME'
        ];

        foreach ($headers as $header) {
            if (!$request->hasHeader($header)) {
                throw new HttpException(
                    "Missing {$header} header",
                    StatusCode::BAD_REQUEST
                );
            }
        }

        $resp = $this->client->sendRequest(
            'POST',
            '/v1/notifications/verify-webhook-signature',
            [
                'auth_algo' => $request->getHeaderLine('PAYPAL-AUTH-ALGO'),
                'cert_url' => $request->getHeaderLine('PAYPAL-CERT-URL'),
                'transmission_id' => $request->getHeaderLine('PAYPAL-TRANSMISSION-ID'),
                'transmission_sig' => $request->getHeaderLine('PAYPAL-TRANSMISSION-SIG'),
                'transmission_time' => $request->getHeaderLine('PAYPAL-TRANSMISSION-TIME'),
                'webhook_id' => $this->webhookId,
                'webhook_event' => json_decode($request->getBody()->getContents(), true)
            ]
        );

        if ($resp->getStatusCode() !== StatusCode::OK->value) {
            throw new HttpException(
                'Invalid webhook signature',
                StatusCode::BAD_REQUEST
            );
        }

        $request->getBody()->rewind();
        $body = json_decode($resp->getBody()->getContents());

        if (
            !isset($body->verification_status)
            || $body->verification_status !== 'SUCCESS'
        ) {
            throw new HttpException(
                'Invalid webhook signature',
                StatusCode::BAD_REQUEST
            );
        }
    }

    private function saveInvoice(object $resoource) {}

    private function cancelSubscription(object $resource)
    {
        $cmd = ReadSubscriptionCommand::createByExternalId(
            'paypal',
            $resource->id
        );

        try {
            $sub = $this->dispatcher->dispatch($cmd);
        } catch (SubscriptionNotFoundException $th) {
            return;
        }

        try {
            $cmd = new CancelSubscriptionCommand($sub);
            $this->dispatcher->dispatch($cmd);
        } catch (PlanNotFoundException $th) {
            //throw $th;
        }
    }
}
