<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Payments\Gateways\Stripe;

use Easy\Container\Attributes\Inject;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Presentation\AccessControls\Permission;
use Presentation\AccessControls\WorkspaceAccessControl;
use Presentation\RequestHandlers\App\Billing\BillingView;
use Presentation\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Throwable;
use User\Domain\Entities\UserEntity;
use Workspace\Domain\Entities\WorkspaceEntity;

#[Route(path: '/customer-portal', method: RequestMethod::POST)]
class CustomerPortalRequestHandler extends BillingView implements
    RequestHandlerInterface
{
    public function __construct(
        private WorkspaceAccessControl $ac,
        private Dispatcher $dispatcher,

        private Client $client,

        #[Inject('option.stripe.is_enabled')]
        private bool $isEnabled = false,

        #[Inject('option.stripe.customer_portal')]
        private bool $isPortalEnabled = false,

        #[Inject('option.site.domain')]
        private ?string $domain = null,

        #[Inject('option.site.is_secure')]
        private ?string $isSecure = null,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->validateRequest($request);

        if (!$this->isEnabled || !$this->isPortalEnabled) {
            return new RedirectResponse('/app/billing/');
        }

        /** @var WorkspaceEntity */
        $ws = $request->getAttribute(WorkspaceEntity::class);
        $sub = $ws->getSubscription();

        if (!$sub) {
            return new RedirectResponse('/app/billing/');
        }

        $id = $sub->getExternalId()->value;

        try {
            $subscription = $this->client->subscriptions->retrieve($id);
            $customer = $subscription->customer;
            $session = $this->client->billingPortal->sessions->create([
                'customer' => $customer,
                'return_url' => $this->generateReturnUrl(),
            ]);
        } catch (Throwable $th) {
            return new RedirectResponse('/app/billing/');
        }

        return new RedirectResponse($session->url);
    }

    private function validateRequest(ServerRequestInterface $req): void
    {
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

    private function generateReturnUrl(): string
    {
        $protocol = $this->isSecure ? 'https' : 'http';
        $domain = $this->domain;

        return sprintf(
            '%s://%s/app/billing/',
            $protocol,
            $domain
        );
    }
}
