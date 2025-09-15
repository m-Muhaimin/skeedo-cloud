<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Api\Workspaces;

use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Presentation\AccessControls\Permission;
use Presentation\AccessControls\WorkspaceAccessControl;
use Presentation\Exceptions\NotFoundException;
use Presentation\Resources\Api\WorkspaceResource;
use Presentation\Response\EmptyResponse;
use Presentation\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use User\Domain\Entities\UserEntity;
use Workspace\Application\Commands\DeleteWorkspaceUserCommand;
use Workspace\Domain\Entities\WorkspaceEntity;
use Workspace\Domain\Exceptions\WorkspaceNotFoundException;
use Workspace\Domain\Exceptions\WorkspaceUserNotFoundException;

#[Route(path: '/[uuid:id]/users/[uuid:uid]', method: RequestMethod::DELETE)]
class RemoveWorkspaceUserRequestHandler extends WorkspaceApi implements
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
            $cmd = new DeleteWorkspaceUserCommand(
                $request->getAttribute("id"),
                $request->getAttribute("uid")
            );

            /** @var WorkspaceEntity */
            $ws = $this->dispatcher->dispatch($cmd);
        } catch (WorkspaceNotFoundException | WorkspaceUserNotFoundException $th) {
            throw new NotFoundException(
                previous: $th,
                param: "id"
            );
        }

        /** @var UserEntity */
        $user = $request->getAttribute(UserEntity::class);

        if ($user->getId()->getValue()->toString() === $request->getAttribute("uid")) {
            return new EmptyResponse();
        } else {
            return new JsonResponse(new WorkspaceResource($ws, ["user"]));
        }
    }

    private function validateRequest(ServerRequestInterface $req): void
    {
        /** @var UserEntity */
        $user = $req->getAttribute(UserEntity::class);

        if ($user->getId()->getValue()->toString() === $req->getAttribute("uid")) {
            // User leaving workspace
            return;
        }

        $this->ac->denyUnlessGranted(
            Permission::WORKSPACE_MANAGE,
            $user,
            $req->getAttribute("id")
        );
    }
}
