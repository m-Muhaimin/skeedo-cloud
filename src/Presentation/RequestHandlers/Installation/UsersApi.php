<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Installation;

use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Presentation\Exceptions\UnprocessableEntityException;
use Presentation\Resources\Api\UserResource;
use Presentation\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Throwable;
use User\Application\Commands\CreateUserCommand;
use User\Domain\Entities\UserEntity;

#[Route(path: '/users', method: RequestMethod::POST)]
class UsersApi extends InstallationApi implements
    RequestHandlerInterface
{
    public function __construct(
        private Dispatcher $dispatcher
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $user = $this->createUserAccount($request);
        } catch (Throwable $th) {
            throw new UnprocessableEntityException($th->getMessage());
        }

        return new JsonResponse(new UserResource($user));
    }

    private function createUserAccount(ServerRequestInterface $request): UserEntity
    {
        $payload = $request->getParsedBody();

        $cmd = new CreateUserCommand(
            $payload->email,
            $payload->first_name,
            $payload->last_name
        );

        $cmd->setPassword($payload->password);
        $cmd->setStatus(1);
        $cmd->setRole(1);

        return $this->dispatcher->dispatch($cmd);
    }
}
