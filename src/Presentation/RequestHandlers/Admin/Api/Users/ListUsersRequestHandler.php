<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin\Api\Users;

use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Iterator;
use Presentation\Exceptions\HttpException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Shared\Infrastructure\CommandBus\Exception\NoHandlerFoundException;
use Presentation\Resources\ListResource;
use Presentation\Response\JsonResponse;
use User\Application\Commands\ListUsersCommand;
use User\Domain\Entities\UserEntity;
use Presentation\Resources\Admin\Api\UserResource;
use Presentation\Validation\ValidationException;
use User\Domain\Exceptions\UserNotFoundException;

#[Route(path: '/', method: RequestMethod::GET)]
class ListUsersRequestHandler extends UserApi implements
    RequestHandlerInterface
{
    /**
     * @param Dispatcher $dispatcher
     * @return void
     */
    public function __construct(
        private Dispatcher $dispatcher
    ) {}

    /**
     * @param ServerRequestInterface $request 
     * @return ResponseInterface 
     * @throws HttpException 
     * @throws NoHandlerFoundException 
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $cmd = new ListUsersCommand();
        $params = (object) $request->getQueryParams();

        if (property_exists($params, 'status')) {
            $cmd->setStatus((int) $params->status);
        }

        if (property_exists($params, 'role')) {
            $cmd->setRole((int) $params->role);
        }

        if (property_exists($params, 'country_code')) {
            $cmd->setCountryCode($params->country_code);
        }

        if (property_exists($params, 'is_email_verified')) {
            $cmd->setIsEmailVerified((bool) $params->is_email_verified);
        }

        if (property_exists($params, 'ref')) {
            $cmd->setRef($params->ref);
        }

        if (property_exists($params, 'created_at')) {
            $parts = explode(':', $params->created_at, 2);
            $cmd->setAfter($parts[0]);

            if (count($parts) > 1) {
                $cmd->setBefore($parts[1]);
            }
        }

        if (property_exists($params, 'query') && $params->query) {
            $cmd->query = $params->query;
        }

        if (property_exists($params, 'sort') && $params->sort) {
            $sort = explode(':', $params->sort);
            $orderBy = $sort[0];
            $dir = $sort[1] ?? 'asc';
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

        try {
            /** @var Iterator<int,UserEntity> $users */
            $users = $this->dispatcher->dispatch($cmd);
        } catch (UserNotFoundException $th) {
            throw new ValidationException(
                'Invalid cursor',
                property_exists($params, 'starting_after')
                    ? 'starting_after'
                    : 'ending_before',
                previous: $th
            );
        }

        $res = new ListResource();
        foreach ($users as $user) {
            $res->pushData(new UserResource($user));
        }

        return new JsonResponse($res);
    }
}
