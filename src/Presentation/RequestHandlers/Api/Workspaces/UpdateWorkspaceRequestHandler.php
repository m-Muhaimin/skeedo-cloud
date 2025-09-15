<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Api\Workspaces;

use Easy\Http\Message\RequestMethod;
use Easy\Http\Message\StatusCode;
use Easy\Router\Attributes\Route;
use Presentation\AccessControls\Permission;
use Presentation\AccessControls\WorkspaceAccessControl;
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
use Workspace\Application\Commands\UpdateWorkspaceCommand;
use Workspace\Domain\Entities\WorkspaceEntity;
use Workspace\Domain\Exceptions\WorkspaceNotFoundException;

#[Route(path: '/[uuid:id]', method: RequestMethod::PATCH)]
#[Route(path: '/[uuid:id]', method: RequestMethod::POST)]
class UpdateWorkspaceRequestHandler extends WorkspaceApi implements
    RequestHandlerInterface
{
    public function __construct(
        private WorkspaceAccessControl $ac,
        private Validator $validator,
        private Dispatcher $dispatcher
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->validateRequest($request);

        /** @var object{name?:string,owner_id?:string} */
        $payload = $request->getParsedBody();

        $id = $request->getAttribute('id');
        $cmd = new UpdateWorkspaceCommand($id);

        if (property_exists($payload, 'name')) {
            $cmd->setName($payload->name);
        }

        if (property_exists($payload, 'address')) {
            $cmd->setAddress(json_decode(json_encode($payload->address), true));
        }

        if (property_exists($payload, 'owner_id')) {
            $cmd->setOwnerId($payload->owner_id);
        }

        if (property_exists($payload, 'openai_api_key')) {
            $cmd->setOpenaiApiKey($payload->openai_api_key);
        }

        if (property_exists($payload, 'anthropic_api_key')) {
            $cmd->setAnthropicApiKey($payload->anthropic_api_key);
        }

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

        return new JsonResponse(new WorkspaceResource($ws, ["user"]));
    }

    private function validateRequest(ServerRequestInterface $req): void
    {
        $this->validator->validateRequest($req, [
            'name' => 'string|max:50',
            'owner_id' => 'sometimes|uuid',
            'address' => 'sometimes|array',
            'openai_api_key' => 'sometimes|string',
            'anthropic_api_key' => 'sometimes|string',
        ]);

        /** @var UserEntity */
        $user = $req->getAttribute(UserEntity::class);

        $this->ac->denyUnlessGranted(
            Permission::WORKSPACE_MANAGE,
            $user,
            $req->getAttribute("id")
        );
    }
}
