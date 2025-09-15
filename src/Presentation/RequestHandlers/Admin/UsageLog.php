<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin;

use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Presentation\RequestHandlers\Admin\AbstractAdminViewRequestHandler;
use Presentation\Resources\Admin\Api\WorkspaceResource;
use Presentation\Response\RedirectResponse;
use Presentation\Response\ViewResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Workspace\Application\Commands\ReadWorkspaceCommand;
use Workspace\Domain\Exceptions\WorkspaceNotFoundException;

#[Route(path: '/workspaces/[uuid:id]/logs/usage', method: RequestMethod::GET)]
class UsageLog extends AbstractAdminViewRequestHandler implements
    RequestHandlerInterface
{
    public function __construct(
        private Dispatcher $dispatcher
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $id = $request->getAttribute('id');

        $cmd = new ReadWorkspaceCommand($id);

        try {
            $ws = $this->dispatcher->dispatch($cmd);
        } catch (WorkspaceNotFoundException $th) {
            return new RedirectResponse('/admin/workspaces');
        }

        return new ViewResponse(
            '/templates/admin/usage-logs.twig',
            ['current_workspace' => new WorkspaceResource($ws)]
        );
    }
}
