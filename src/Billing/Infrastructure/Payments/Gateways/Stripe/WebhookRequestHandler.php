<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Payments\Gateways\Stripe;

use Billing\Application\Commands\CancelSubscriptionCommand;
use Billing\Application\Commands\ReadSubscriptionCommand;
use Billing\Domain\Entities\SubscriptionEntity;
use Billing\Domain\Exceptions\PlanNotFoundException;
use Billing\Domain\Exceptions\SubscriptionNotFoundException;
use Billing\Infrastructure\Payments\WebhookHandlerInterface;
use Easy\Container\Attributes\Inject;
use Easy\Http\Message\StatusCode;
use Presentation\Exceptions\HttpException;
use Psr\Http\Message\ServerRequestInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Shared\Infrastructure\CommandBus\Exception\NoHandlerFoundException;
use Stripe\Event;
use Stripe\StripeObject;
use Stripe\Subscription;
use UnexpectedValueException;

class WebhookRequestHandler implements WebhookHandlerInterface
{
    /**
     * @param Dispatcher $dispatcher 
     * @param null|string $secret 
     * @return void 
     */
    public function __construct(
        private Dispatcher $dispatcher,

        #[Inject('option.stripe.webhook_secret')]
        private ?string $secret = null
    ) {}

    public function handle(ServerRequestInterface $request): void
    {
        $this->validateRequest($request);

        $header = $request->getHeaderLine('Stripe-Signature');

        $request->getBody()->rewind();
        $payload = $request->getBody()->getContents();

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $header,
                $this->secret
            );
        } catch (UnexpectedValueException $e) {
            // Invalid payload
            throw new HttpException(
                $e->getMessage(),
                StatusCode::BAD_REQUEST
            );
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            // Invalid signature
            throw new HttpException(
                $e->getMessage(),
                StatusCode::BAD_REQUEST
            );
        }

        $type = $event->type;
        $object = $event->data->object;

        switch ($type) {
            case Event::INVOICE_CREATED:
            case Event::INVOICE_UPDATED:
            case Event::INVOICE_FINALIZED:
            case Event::INVOICE_MARKED_UNCOLLECTIBLE:
            case Event::INVOICE_PAYMENT_SUCCEEDED:
                $this->saveInvoice($object);
                break;

            case Event::INVOICE_DELETED:
            case Event::INVOICE_VOIDED:
                $this->deleteInvoice($object);
                break;

            case Event::CUSTOMER_SUBSCRIPTION_UPDATED:
            case Event::CUSTOMER_SUBSCRIPTION_DELETED:
                $this->updateSubscription($object);
                break;
        }
    }

    /**
     * @param ServerRequestInterface $request 
     * @return void 
     * @throws HttpException 
     */
    private  function validateRequest(ServerRequestInterface $request): void
    {
        if (!$this->secret) {
            throw new HttpException(
                'Stripe webhook secret is not set',
                StatusCode::INTERNAL_SERVER_ERROR
            );
        }

        $header = $request->getHeaderLine('Stripe-Signature');

        if (!$header) {
            throw new HttpException(
                'Missing Stripe-Signature header',
                StatusCode::BAD_REQUEST
            );
        }
    }

    private function saveInvoice(StripeObject $invoice)
    {
        if (!$invoice->subscription) {
            // This is not a subscription invoice
            return;
        }
    }

    private function deleteInvoice(StripeObject $invoice) {}

    /**
     * @param Subscription $object 
     * @return void 
     * @throws NoHandlerFoundException 
     */
    private function updateSubscription(Subscription $object)
    {
        $cmd = ReadSubscriptionCommand::createByExternalId(
            'stripe',
            $object->id
        );

        try {
            $sub = $this->dispatcher->dispatch($cmd);
        } catch (SubscriptionNotFoundException $th) {
            return;
        }

        if (in_array($object->status, [
            Subscription::STATUS_CANCELED,
            Subscription::STATUS_INCOMPLETE_EXPIRED
        ])) {
            $this->cancelSubscription($sub);
            return;
        }
    }

    /**
     * @param SubscriptionEntity $sub 
     * @return void 
     * @throws NoHandlerFoundException 
     */
    private function cancelSubscription(SubscriptionEntity $sub)
    {
        try {
            $cmd = new CancelSubscriptionCommand($sub);
            $this->dispatcher->dispatch($cmd);
        } catch (PlanNotFoundException $th) {
            //throw $th;
        }
    }
}
