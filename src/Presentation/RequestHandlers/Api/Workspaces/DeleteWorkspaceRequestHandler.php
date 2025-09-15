<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Api\Workspaces;

use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Exception;
use LogicException;
use Presentation\AccessControls\Permission;
use Presentation\AccessControls\WorkspaceAccessControl;
use Presentation\Exceptions\HttpException;
use Presentation\Exceptions\NotFoundException;
use Presentation\Response\EmptyResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Shared\Infrastructure\CommandBus\Exception\NoHandlerFoundException;
use User\Domain\Entities\UserEntity;
use Workspace\Application\Commands\DeleteWorkspaceCommand;
use Workspace\Domain\Exceptions\WorkspaceNotFoundException;

#[Route(path: '/[uuid:id]', method: RequestMethod::DELETE)]
class DeleteWorkspaceRequestHandler extends WorkspaceApi implements
    RequestHandlerInterface
{
    public function __construct(
        private WorkspaceAccessControl $ac,
        private Dispatcher $dispatcher
    ) {
    }

    /**
     * @throws NoHandlerFoundException
     * @throws NotFoundException
     * @throws Exception
     * @throws LogicException
     * @throws HttpException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var UserEntity */
        $user = $request->getAttribute(UserEntity::class);
        $this->ac->denyUnlessGranted(
            Permission::WORKSPACE_MANAGE,
            $user,
            $request->getAttribute("id")
        );

        try {
            $cmd = new DeleteWorkspaceCommand($request->getAttribute("id"));
            $this->dispatcher->dispatch($cmd);
        } catch (WorkspaceNotFoundException $th) {
            throw new NotFoundException(
                param: 'id',
                previous: $th
            );
        }

        return new EmptyResponse();
    }
}
