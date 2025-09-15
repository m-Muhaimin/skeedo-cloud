<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin\Api\Workspaces;

use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Presentation\Resources\Admin\Api\WorkspaceResource;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Presentation\Response\EmptyResponse;
use Shared\Infrastructure\CommandBus\Exception\NoHandlerFoundException;
use Shared\Infrastructure\ExportService;
use Symfony\Component\Mime\Exception\InvalidArgumentException;
use Symfony\Component\Mime\Exception\LogicException;
use Traversable;
use User\Domain\Entities\UserEntity;
use Workspace\Application\Commands\ListWorkspacesCommand;
use Workspace\Domain\Entities\WorkspaceEntity;

#[Route(path: '/export', method: RequestMethod::POST)]
class ExportWorkspacesRequestHandler extends WorkspaceApi implements
    RequestHandlerInterface
{
    public function __construct(
        private Dispatcher $dispatcher,
        private ExportService $service
    ) {
    }

    /**
     * @throws NoHandlerFoundException
     * @throws InvalidArgumentException
     * @throws LogicException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var UserEntity */
        $user = $request->getAttribute(UserEntity::class);

        $this->service->exportToEmail(
            $user->getEmail(),
            $this->getWorkspaces($request)
        );

        return new EmptyResponse();
    }

    /**
     * @return Traversable<WorkspaceResource>
     * @throws NoHandlerFoundException
     */
    private function getWorkspaces(ServerRequestInterface $request): Traversable
    {
        $params = (object) $request->getQueryParams();

        $cmd = new ListWorkspacesCommand();
        $cmd->sortDirection = null; // no sorting by default
        $cmd->maxResults = null; // no limit

        if (property_exists($params, 'with_subscription')) {
            $cmd->hasSubscription = (int) $params->with_subscription === 1;
        }

        if (property_exists($params, 'query') && $params->query) {
            $cmd->query = $params->query;
        }

        if (property_exists($params, 'sort') && $params->sort) {
            $sort = explode(':', $params->sort);
            $orderBy = $sort[0];
            $dir = $sort[1] ?? 'desc';
            $cmd->setOrderBy($orderBy, $dir);
        }

        if (property_exists($params, 'starting_after') && $params->starting_after) {
            $cmd->setCursor(
                $params->starting_after,
                'starting_after'
            );
        } elseif (property_exists($params, 'ending_before') && $params->ending_before) {
            $cmd->setCursor(
                $params->ending_before,
                'ending_before'
            );
        }

        if (property_exists($params, 'limit')) {
            $cmd->setLimit((int) $params->limit);
        }

        /** @var Traversable<int,WorkspaceEntity> $workspaces */
        $workspaces = $this->dispatcher->dispatch($cmd);

        foreach ($workspaces as $ws) {
            yield new WorkspaceResource($ws, ['user']);
        }
    }
}
