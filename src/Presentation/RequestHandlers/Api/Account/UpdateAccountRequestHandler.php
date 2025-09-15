<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Api\Account;

use Easy\Container\Attributes\Inject;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Middleware;
use Easy\Router\Attributes\Route;
use Presentation\Middlewares\DemoEnvironmentMiddleware;
use Presentation\Resources\Api\UserResource;
use Presentation\Response\JsonResponse;
use Presentation\Validation\ValidationException;
use Presentation\Validation\Validator;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Shared\Infrastructure\CommandBus\Exception\NoHandlerFoundException;
use User\Application\Commands\UpdateUserCommand;
use User\Domain\Entities\UserEntity;
use User\Domain\Exceptions\UserNotFoundException;

#[Middleware(DemoEnvironmentMiddleware::class)]
#[Route(path: '/', method: RequestMethod::PUT)]
#[Route(path: '/', method: RequestMethod::POST)]
class UpdateAccountRequestHandler extends AccountApi implements
    RequestHandlerInterface
{
    /**
     * @param Validator $validator 
     * @param Dispatcher $dispatcher 
     * @return void 
     */
    public function __construct(
        private Validator $validator,
        private Dispatcher $dispatcher,

        #[Inject('option.site.phone_requirement_policy')]
        private string $phoneRequirementPolicy = 'none',
    ) {}

    /**
     * @param ServerRequestInterface $request 
     * @return ResponseInterface 
     * @throws ValidationException 
     * @throws NoHandlerFoundException 
     * @throws UserNotFoundException 
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {

        /** @var UserEntity */
        $user = $request->getAttribute(UserEntity::class);

        $this->validateRequest($request);
        $payload = (object) $request->getParsedBody();

        $cmd = new UpdateUserCommand($user);

        if (property_exists($payload, 'first_name')) {
            $cmd->setFirstName($payload->first_name);
        }

        if (property_exists($payload, 'last_name')) {
            $cmd->setLastName($payload->last_name);
        }

        if (property_exists($payload, 'phone_number')) {
            if ($this->phoneRequirementPolicy === 'strict') {
                $cmd->setPhoneNumber($payload->phone_number);
            } else {
                $cmd->setPhoneNumber($payload->phone_number ?: null);
            }
        }

        if (property_exists($payload, 'language')) {
            $cmd->setLanguage($payload->language);
        }

        if (property_exists($payload, 'preferences')) {
            $cmd->preferences = (array) $payload->preferences;
        }

        $user = $this->dispatcher->dispatch($cmd);
        return new JsonResponse(new UserResource($user));
    }

    /**
     * @param ServerRequestInterface $req 
     * @return void 
     * @throws ValidationException 
     */
    private function validateRequest(ServerRequestInterface $req): void
    {
        $rules = [
            'first_name' => 'string|max:50',
            'last_name' => 'string|max:50',
            'language' => 'string|max:5',
            'phone_number' => 'string|max:30',
            'preferences' => 'array',
        ];

        $this->validator->validateRequest($req, $rules);
    }
}
