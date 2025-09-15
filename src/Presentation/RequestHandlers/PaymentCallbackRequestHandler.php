<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers;

use Billing\Application\Commands\CancelSubscriptionCommand;
use Billing\Application\Commands\FulfillOrderCommand;
use Billing\Application\Commands\PayOrderCommand;
use Billing\Application\Commands\ReadOrderCommand;
use Billing\Domain\Entities\OrderEntity;
use Billing\Domain\Exceptions\AlreadyFulfilledException;
use Billing\Domain\Exceptions\AlreadyPaidException;
use Billing\Domain\Exceptions\OrderNotFoundException;
use Billing\Infrastructure\Payments\Exceptions\GatewayNotFoundException;
use Billing\Infrastructure\Payments\Exceptions\PaymentException;
use Billing\Infrastructure\Payments\PaymentGatewayFactoryInterface;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Presentation\Response\RedirectResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;

#[Route(path: '/payment-callback/[uuid:oid]/[:gateway]', method: RequestMethod::GET)]
#[Route(path: '/payment-callback/[uuid:oid]/[:gateway]', method: RequestMethod::POST)]
class PaymentCallbackRequestHandler extends AbstractRequestHandler implements
    RequestHandlerInterface
{
    public function __construct(
        private Dispatcher $dispatcher,
        private PaymentGatewayFactoryInterface $factory,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $cmd = new ReadOrderCommand($request->getAttribute('oid'));

            /** @var OrderEntity */
            $order = $this->dispatcher->dispatch($cmd);
        } catch (OrderNotFoundException $th) {
            return new RedirectResponse('/app');
        }

        $ws = $order->getWorkspace();
        // Current subscription, cancel after new subscription is created
        $sub = $ws->getSubscription();

        try {
            $gateway = $this->factory->create(
                $request->getAttribute('gateway')
            );
        } catch (GatewayNotFoundException $th) {
            return new RedirectResponse('/app');
        }

        $params = $request->getQueryParams();

        if ($request->getMethod() == RequestMethod::POST->value) {
            $params = [...$params, ...json_decode(
                json_encode($request->getParsedBody() ?: []),
                true
            )];
        }

        try {
            $id = $gateway->completePurchase($order, $params);
        } catch (PaymentException $th) {
            return new RedirectResponse(
                '/app/billing/orders/'
                    . $order->getId()->getValue() . '/receipt'
            );
        }

        try {
            $cmd = new PayOrderCommand(
                $order,
                $request->getAttribute('gateway'),
                $id
            );

            $this->dispatcher->dispatch($cmd);
        } catch (AlreadyFulfilledException $th) {
            return new RedirectResponse(
                '/app/billing/orders/'
                    . $order->getId()->getValue() . '/receipt'
            );
        } catch (AlreadyPaidException $th) {
            // Already paid, go to next step
        }

        try {
            $cmd = new FulfillOrderCommand($order);
            $this->dispatcher->dispatch($cmd);
        } catch (AlreadyFulfilledException $th) {
            // Already fulfilled, go to next step
        }

        // Cancel current subscription
        if ($sub && $order->getPlan()->getBillingCycle()->isRenewable()) {
            $cmd = new CancelSubscriptionCommand($sub);
            $this->dispatcher->dispatch($cmd);
        }

        return new RedirectResponse(
            '/app/billing/orders/'
                . $order->getId()->getValue() . '/receipt'
        );
    }
}
