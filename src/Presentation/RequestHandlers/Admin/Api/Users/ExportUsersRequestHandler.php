<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin\Api\Users;

use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use User\Application\Commands\ListUsersCommand;
use User\Domain\Entities\UserEntity;
use Presentation\Resources\Admin\Api\UserResource;
use Presentation\Response\EmptyResponse;
use Shared\Infrastructure\CommandBus\Exception\NoHandlerFoundException;
use Shared\Infrastructure\ExportService;
use Symfony\Component\Mime\Exception\InvalidArgumentException;
use Symfony\Component\Mime\Exception\LogicException;
use Traversable;

#[Route(path: '/export', method: RequestMethod::POST)]
class ExportUsersRequestHandler extends UserApi implements
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
            $this->getUsers($request)
        );

        return new EmptyResponse();
    }

    /**
     * @return Traversable<UserResource>
     * @throws NoHandlerFoundException
     */
    private function getUsers(ServerRequestInterface $request): Traversable
    {
        $params = (object) $request->getQueryParams();

        $cmd = new ListUsersCommand();
        $cmd->sortDirection = null; // no sorting by default
        $cmd->maxResults = null; // no limit

        if (property_exists($params, 'status')) {
            $cmd->setStatus((int) $params->status);
        }

        if (property_exists($params, 'role')) {
            $cmd->setRole((int) $params->role);
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

        /** @var Traversable<int,UserEntity> $users */
        $users = $this->dispatcher->dispatch($cmd);

        foreach ($users as $u) {
            yield new UserResource($u);
        }
    }
}
