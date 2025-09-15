<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin\Api\Users;

use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Presentation\Exceptions\NotFoundException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Shared\Infrastructure\CommandBus\Exception\NoHandlerFoundException;
use Presentation\Response\JsonResponse;
use Presentation\Resources\Admin\Api\UserResource;
use Presentation\Validation\ValidationException;
use Presentation\Validation\Validator;
use User\Application\Commands\UpdateUserCommand;
use User\Domain\Exceptions\UserNotFoundException;
use User\Domain\ValueObjects\Role;
use User\Domain\ValueObjects\Status;

#[Route(path: '/[uuid:id]', method: RequestMethod::PATCH)]
#[Route(path: '/[uuid:id]', method: RequestMethod::POST)]
class UpdateUserRequestHandler extends UserApi implements
    RequestHandlerInterface
{
    /**
     * @param Validator $validator 
     * @param Dispatcher $dispatcher 
     * @return void 
     */
    public function __construct(
        private Validator $validator,
        private Dispatcher $dispatcher
    ) {}

    /**
     * @param ServerRequestInterface $request 
     * @return ResponseInterface 
     * @throws ValidationException 
     * @throws NotFoundException 
     * @throws NoHandlerFoundException 
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->validateRequest($request);
        $payload = (object) $request->getParsedBody();

        $cmd = new UpdateUserCommand($request->getAttribute('id'));

        if (property_exists($payload, 'first_name')) {
            $cmd->setFirstName($payload->first_name);
        }

        if (property_exists($payload, 'last_name')) {
            $cmd->setLastName($payload->last_name);
        }

        if (property_exists($payload, 'phone_number')) {
            $cmd->setPhoneNumber($payload->phone_number ?: null);
        }

        if (property_exists($payload, 'language')) {
            $cmd->setLanguage($payload->language);
        }

        if (property_exists($payload, 'workspace_cap')) {
            $cmd->setWorkspaceCap(
                $payload->workspace_cap === null
                    ? $payload->workspace_cap
                    : (int) $payload->workspace_cap
            );
        }

        if (property_exists($payload, 'status')) {
            $cmd->setStatus((int) $payload->status);
        }

        if (property_exists($payload, 'role')) {
            $cmd->setRole((int) $payload->role);
        }

        try {
            $user = $this->dispatcher->dispatch($cmd);
        } catch (UserNotFoundException $th) {
            throw new NotFoundException(
                param: 'id',
                previous: $th
            );
        }

        return new JsonResponse(
            new UserResource($user, ['workspace'])
        );
    }

    /**
     * @param ServerRequestInterface $req 
     * @return void 
     * @throws ValidationException 
     */
    private function validateRequest(ServerRequestInterface $req): void
    {
        $this->validator->validateRequest($req, [
            'first_name' => 'string|max:50',
            'last_name' => 'string|max:50',
            'phone_number' => 'string|max:30',
            'workspace_cap' => 'integer|min:0',
            'status' => 'integer|in:' . implode(",", array_map(
                fn(Status $type) => $type->value,
                Status::cases()
            )),
            'role' => 'integer|in:' . implode(",", array_map(
                fn(Role $type) => $type->value,
                Role::cases()
            ))
        ]);
    }
}
