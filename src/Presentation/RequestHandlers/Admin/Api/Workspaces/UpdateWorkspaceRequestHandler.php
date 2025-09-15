<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin\Api\Workspaces;

use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Presentation\Exceptions\NotFoundException;
use Presentation\Resources\Admin\Api\WorkspaceResource;
use Presentation\Response\JsonResponse;
use Presentation\Validation\Validator;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Workspace\Application\Commands\UpdateWorkspaceCommand;
use Workspace\Domain\Entities\WorkspaceEntity;
use Workspace\Domain\Exceptions\WorkspaceNotFoundException;

#[Route(path: '/[uuid:id]', method: RequestMethod::PATCH)]
#[Route(path: '/[uuid:id]', method: RequestMethod::POST)]
class UpdateWorkspaceRequestHandler extends WorkspaceApi implements
    RequestHandlerInterface
{
    public function __construct(
        private Validator $validator,
        private Dispatcher $dispatcher
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {

        $this->validateRequest($request);

        /** @var object{name?:string} */
        $payload = $request->getParsedBody();

        $id = $request->getAttribute('id');
        $cmd = new UpdateWorkspaceCommand($id);

        if (property_exists($payload, 'name')) {
            $cmd->setName($payload->name);
        }

        if (property_exists($payload, 'credit_count')) {
            $cmd->setCreditCount($payload->credit_count === '' ? null : (float) $payload->credit_count);
        }

        
        try {
            /** @var WorkspaceEntity */
            $ws = $this->dispatcher->dispatch($cmd);
        } catch (WorkspaceNotFoundException $th) {
            throw new NotFoundException(
                param: 'id',
                previous: $th
            );
        }

        return new JsonResponse(new WorkspaceResource($ws, ["user"]));
    }

    private function validateRequest(ServerRequestInterface $req): void
    {
        $this->validator->validateRequest($req, [
            'name' => 'string|max:50',
            'credit_count' => 'nullable|numeric|min:0'
        ]);
    }
}
