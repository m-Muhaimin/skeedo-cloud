<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Api\Account;

use Easy\Http\Message\RequestMethod;
use Easy\Http\Message\StatusCode;
use Easy\Router\Attributes\Route;
use Presentation\Exceptions\HttpException;
use Presentation\Resources\Api\UserResource;
use Presentation\Response\JsonResponse;
use Presentation\Validation\Validator;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use User\Domain\Entities\UserEntity;
use Workspace\Application\Commands\SetCurrentWorkspaceCommand;
use Workspace\Domain\Exceptions\WorkspaceNotFoundException;

#[Route(path: '/workspace', method: RequestMethod::PUT)]
#[Route(path: '/workspace', method: RequestMethod::POST)]
class SetWorkspaceRequestHandler extends AccountApi implements
    RequestHandlerInterface
{
    public function __construct(
        private Validator $validator,
        private Dispatcher $dispatcher
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var UserEntity */
        $user = $request->getAttribute(UserEntity::class);

        $this->validateRequest($request);

        /** @var object{id:string} */
        $payload = $request->getParsedBody();

        $cmd = new SetCurrentWorkspaceCommand(
            $user,
            $payload->id
        );

        try {
            $this->dispatcher->dispatch($cmd);
        } catch (WorkspaceNotFoundException $th) {
            throw new HttpException(
                $th->getMessage(),
                previous: $th,
                statusCode: StatusCode::UNPROCESSABLE_ENTITY
            );
        }

        return new JsonResponse(new UserResource($user));
    }

    private function validateRequest(ServerRequestInterface $req): void
    {
        $this->validator->validateRequest($req, [
            'id' => 'required|uuid',
        ]);
    }
}
