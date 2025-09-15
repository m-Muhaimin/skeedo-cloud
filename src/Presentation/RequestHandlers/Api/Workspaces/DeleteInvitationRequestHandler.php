<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Api\Workspaces;

use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Presentation\AccessControls\Permission;
use Presentation\AccessControls\WorkspaceAccessControl;
use Presentation\Exceptions\NotFoundException;
use Presentation\Resources\Api\WorkspaceResource;
use Presentation\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use User\Domain\Entities\UserEntity;
use Workspace\Application\Commands\DeleteInvitationCommand;
use Workspace\Domain\Entities\WorkspaceEntity;
use Workspace\Domain\Exceptions\InvitationNotFoundException;
use Workspace\Domain\Exceptions\WorkspaceNotFoundException;

#[Route(path: '/[uuid:wid]/invitations/[uuid:id]', method: RequestMethod::DELETE)]
class DeleteInvitationRequestHandler extends WorkspaceApi implements
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

        try {
            $cmd = new DeleteInvitationCommand(
                $request->getAttribute("wid"),
                $request->getAttribute("id")
            );

            /** @var WorkspaceEntity */
            $ws = $this->dispatcher->dispatch($cmd);
        } catch (WorkspaceNotFoundException | InvitationNotFoundException $th) {
            throw new NotFoundException(
                previous: $th,
                param: "id"
            );
        }

        return new JsonResponse(
            new WorkspaceResource($ws, ["user"])
        );
    }

    private function validateRequest(ServerRequestInterface $req): void
    {
        /** @var UserEntity */
        $user = $req->getAttribute(UserEntity::class);

        $this->ac->denyUnlessGranted(
            Permission::WORKSPACE_MANAGE,
            $user,
            $req->getAttribute("wid")
        );
    }
}
