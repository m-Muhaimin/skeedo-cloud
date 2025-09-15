<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Api\Billing;

use Billing\Application\Commands\CancelSubscriptionCommand;
use Billing\Application\Commands\CreateOrderCommand;
use Billing\Application\Commands\FulfillOrderCommand;
use Billing\Application\Commands\PayOrderCommand;
use Billing\Domain\Entities\OrderEntity;
use Billing\Domain\Entities\PlanEntity;
use Billing\Domain\ValueObjects\BillingCycle;
use Billing\Domain\ValueObjects\CreditCount;
use Billing\Domain\ValueObjects\Price;
use Billing\Domain\ValueObjects\Title;
use Billing\Infrastructure\Payments\PaymentGatewayFactoryInterface;
use Billing\Infrastructure\Payments\Exceptions\PaymentException;
use Billing\Infrastructure\Payments\PurchaseToken;
use Easy\Container\Attributes\Inject;
use Easy\Http\Message\RequestMethod;
use Easy\Http\Message\StatusCode;
use Easy\Router\Attributes\Route;
use Presentation\AccessControls\Permission;
use Presentation\AccessControls\WorkspaceAccessControl;
use Presentation\Exceptions\UnprocessableEntityException;
use Presentation\Resources\Api\OrderResource;
use Presentation\Response\JsonResponse;
use Presentation\Validation\Validator;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Symfony\Component\Intl\Currencies;
use User\Domain\Entities\UserEntity;
use Workspace\Domain\Entities\WorkspaceEntity;

#[Route(path: '/checkout', method: RequestMethod::POST)]
class CheckoutRequestHandler extends BillingApi implements
    RequestHandlerInterface
{
    public function __construct(
        private Validator $validator,
        private WorkspaceAccessControl $ac,
        private Dispatcher $dispatcher,
        private PaymentGatewayFactoryInterface $factory,

        #[Inject('option.billing.custom_credits.enabled')]
        private bool $customCreditsEnabled = false,

        #[Inject('option.billing.custom_credits.rate')]
        private int $customCreditsRate = 0,

        #[Inject('option.billing.currency')]
        private ?string $currency = "USD",
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->validateRequest($request);
        $payload = (object) $request->getParsedBody();

        /** @var WorkspaceEntity */
        $ws = $request->getAttribute(WorkspaceEntity::class);

        // Current subscription, cancel after new subscription is created
        $sub = $ws->getSubscription();

        $plan = $payload->id ?? null;
        $custom = false;
        if (!$plan) {
            if (!$this->customCreditsEnabled) {
                throw new UnprocessableEntityException('Custom credits are not enabled');
            }

            if (!$ws->getSubscription()) {
                throw new UnprocessableEntityException('Workspace does not have a subscription');
            }

            $amount = $payload->amount;
            if (!$amount || $amount <= 0) {
                throw new UnprocessableEntityException('Invalid amount');
            }

            $fraction = Currencies::getFractionDigits($this->currency);
            $credits = ($amount / 10 ** $fraction) * $this->customCreditsRate;

            // This is temporary plan, it won't be saved to the database
            $plan = new PlanEntity(
                new Title('Credit purchase'),
                new Price($amount),
                BillingCycle::ONE_TIME
            );

            $plan->setCreditCount(new CreditCount($credits));

            $plan = $plan->getSnapshot();
            $plan->unlink();
            $custom = true;
        }

        // Create an order...
        $cmd = new CreateOrderCommand($ws, $plan);

        if (
            !$custom // Custom credits don't support coupons
            && property_exists($payload, 'coupon')
            && $payload->coupon
        ) {
            $cmd->setCoupon($payload->coupon);
        }

        /** @var OrderEntity */
        $order = $this->dispatcher->dispatch($cmd);

        if ($order->getTotalPrice()->value > 0 && !$order->isPaid()) {
            // Pay for order...
            $gateway = $this->factory->create($payload->gateway);

            try {
                $resp = $gateway->purchase($order);
            } catch (PaymentException $th) {
                throw new UnprocessableEntityException(
                    previous: $th,
                );
            }

            if ($resp instanceof UriInterface) {
                return new JsonResponse(
                    [
                        'redirect' => (string) $resp
                    ]
                );
            }

            if ($resp instanceof PurchaseToken) {
                return new JsonResponse([
                    'id' => $order->getId()->getValue()->toString(),
                    'purchase_token' => $resp->value
                ]);
            }

            $cmd = new PayOrderCommand($order, $payload->gateway, $resp);
            $this->dispatcher->dispatch($cmd);
        }

        $cmd = new FulfillOrderCommand($order);
        $resp = $this->dispatcher->dispatch($cmd);

        // Cancel current subscription
        if ($sub) {
            $cmd = new CancelSubscriptionCommand($sub);
            $this->dispatcher->dispatch($cmd);
        }

        return new JsonResponse(new OrderResource($order), StatusCode::CREATED);
    }

    private function validateRequest(ServerRequestInterface $req): void
    {
        $this->validator->validateRequest($req, [
            'id' => 'required_without:amount|uuid|nullable',
            'amount' => 'required_without:id|integer|nullable',
            'gateway' => 'string',
            'coupon' => 'string|nullable'
        ]);

        /** @var UserEntity */
        $user = $req->getAttribute(UserEntity::class);

        /** @var WorkspaceEntity */
        $workspace = $req->getAttribute(WorkspaceEntity::class);

        $this->ac->denyUnlessGranted(
            Permission::WORKSPACE_MANAGE,
            $user,
            $workspace
        );
    }
}
