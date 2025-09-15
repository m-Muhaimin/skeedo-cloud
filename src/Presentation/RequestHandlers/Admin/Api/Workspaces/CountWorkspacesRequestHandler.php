<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin\Api\Workspaces;

use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Presentation\Response\JsonResponse;
use Presentation\Resources\CountResource;
use Shared\Infrastructure\CommandBus\Exception\NoHandlerFoundException;
use Workspace\Application\Commands\CountWorkspacesCommand;

#[Route(path: '/count', method: RequestMethod::GET)]
class CountWorkspacesRequestHandler extends WorkspaceApi implements
    RequestHandlerInterface
{
    public function __construct(
        private Dispatcher $dispatcher
    ) {
    }

    /**
     * @throws NoHandlerFoundException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $cmd = new CountWorkspacesCommand();
        $params = (object) $request->getQueryParams();

        if (property_exists($params, 'with_subscription')) {
            $cmd->hasSubscription = (int) $params->with_subscription === 1;
        }

        if (property_exists($params, 'query') && $params->query) {
            $cmd->query = $params->query;
        }

        $count = $this->dispatcher->dispatch($cmd);
        return new JsonResponse(new CountResource($count));
    }
}
