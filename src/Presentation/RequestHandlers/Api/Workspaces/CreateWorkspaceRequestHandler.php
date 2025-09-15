<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Api\Workspaces;

use Easy\Http\Message\RequestMethod;
use Easy\Http\Message\StatusCode;
use Easy\Router\Attributes\Route;
use Presentation\Exceptions\HttpException;
use Presentation\Exceptions\NotFoundException;
use Presentation\Resources\Api\WorkspaceResource;
use Presentation\Response\JsonResponse;
use Presentation\Validation\Validator;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use User\Domain\Entities\UserEntity;
use User\Domain\Exceptions\OwnedWorkspaceCapException;
use Workspace\Application\Commands\CreateWorkspaceCommand;
use Workspace\Domain\Entities\WorkspaceEntity;
use Workspace\Domain\Exceptions\WorkspaceNotFoundException;

#[Route(path: '/', method: RequestMethod::POST)]
class CreateWorkspaceRequestHandler extends WorkspaceApi implements
    RequestHandlerInterface
{
    public function __construct(
        private Validator $validator,
        private Dispatcher $dispatcher
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var UserEntity */
        $user = $request->getAttribute(UserEntity::class);

        $this->validateRequest($request);

        /** @var object{name:string} */
        $payload = $request->getParsedBody();

        $cmd = new CreateWorkspaceCommand($user, $payload->name);

        try {
            /** @var WorkspaceEntity */
            $ws = $this->dispatcher->dispatch($cmd);
        } catch (WorkspaceNotFoundException $th) {
            throw new NotFoundException(previous: $th);
        } catch (OwnedWorkspaceCapException $th) {
            throw new HttpException(
                message: $th->getMessage(),
                statusCode: StatusCode::UNPROCESSABLE_ENTITY,
                previous: $th
            );
        }

        return new JsonResponse(
            new WorkspaceResource($ws, ["user"]),
            StatusCode::CREATED
        );
    }

    private function validateRequest(ServerRequestInterface $req): void
    {
        $this->validator->validateRequest($req, [
            'name' => 'required|string|max:50',
        ]);
    }
}
