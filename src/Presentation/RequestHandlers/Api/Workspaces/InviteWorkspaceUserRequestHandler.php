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
use Presentation\Validation\Validator;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use User\Domain\Entities\UserEntity;
use Workspace\Application\Commands\InviteMemberCommand;
use Workspace\Domain\Entities\WorkspaceInvitationEntity;
use Workspace\Domain\Exceptions\WorkspaceNotFoundException;

#[Route(path: '/[uuid:id]/invitations', method: RequestMethod::POST)]
class InviteWorkspaceUserRequestHandler extends WorkspaceApi implements
    RequestHandlerInterface
{
    public function __construct(
        private WorkspaceAccessControl $ac,
        private Validator $validator,
        private Dispatcher $dispatcher
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->validateRequest($request);

        /** @var object{email:string} */
        $payload = $request->getParsedBody();

        try {
            $cmd = new InviteMemberCommand(
                $request->getAttribute("id"),
                $payload->email
            );

            /** @var WorkspaceInvitationEntity */
            $invitation = $this->dispatcher->dispatch($cmd);
        } catch (WorkspaceNotFoundException $th) {
            throw new NotFoundException(previous: $th);
        }

        return new JsonResponse(
            new WorkspaceResource($invitation->getWorkspace(), ["user"])
        );
    }

    private function validateRequest(ServerRequestInterface $req): void
    {
        /** @var UserEntity */
        $user = $req->getAttribute(UserEntity::class);

        $this->validator->validateRequest($req, [
            'email' => 'required|email',
        ]);

        $this->ac->denyUnlessGranted(
            Permission::WORKSPACE_MANAGE,
            $user,
            $req->getAttribute("id")
        );
    }
}
