<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Api\Billing;

use Billing\Application\Commands\CancelSubscriptionCommand;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Presentation\AccessControls\Permission;
use Presentation\AccessControls\WorkspaceAccessControl;
use Presentation\Response\EmptyResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use User\Domain\Entities\UserEntity;
use Workspace\Domain\Entities\WorkspaceEntity;

#[Route(path: '/subscription', method: RequestMethod::DELETE)]
class CancelSubscriptionRequestHandler extends BillingApi implements
    RequestHandlerInterface
{
    public function __construct(
        private WorkspaceAccessControl $ac,
        private Dispatcher $dispatcher
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->validateRequest($request);

        /** @var WorkspaceEntity */
        $ws = $request->getAttribute(WorkspaceEntity::class);

        $sub = $ws->getSubscription();

        if ($sub) {
            $cmd = new CancelSubscriptionCommand($sub);
            $this->dispatcher->dispatch($cmd);
        }

        return new EmptyResponse();
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
}
