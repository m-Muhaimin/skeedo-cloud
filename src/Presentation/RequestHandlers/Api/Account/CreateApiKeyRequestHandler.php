<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Api\Account;

use Easy\Container\Attributes\Inject;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Middleware;
use Easy\Router\Attributes\Route;
use Override;
use Presentation\Exceptions\NotFoundException;
use Presentation\Middlewares\DemoEnvironmentMiddleware;
use Presentation\Resources\Api\UserResource;
use Presentation\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Shared\Infrastructure\CommandBus\Exception\NoHandlerFoundException;
use User\Application\Commands\GenerateApiKeyCommand;
use User\Domain\Entities\UserEntity;

#[Middleware(DemoEnvironmentMiddleware::class)]
#[Route(path: '/rest-api-keys', method: RequestMethod::POST)]
class CreateApiKeyRequestHandler extends AccountApi implements
    RequestHandlerInterface
{
    public function __construct(
        private Dispatcher $dispatcher,

        #[Inject('option.features.api.is_enabled')]
        private ?bool $isApiEnabled = null,

        #[Inject('option.features.admin_api.is_enabled')]
        private ?bool $isAdminApiEnabled = null,
    ) {
    }

    /**
     * @throws NoHandlerFoundException
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var UserEntity */
        $user = $request->getAttribute(UserEntity::class);

        $enabled =
            ($user->getRole()->value == 'admin' && $this->isAdminApiEnabled)
            || $this->isApiEnabled;

        if (!$enabled) {
            throw new NotFoundException();
        }

        $cmd = new GenerateApiKeyCommand($user);
        $user = $this->dispatcher->dispatch($cmd);

        return new JsonResponse(new UserResource($user));
    }
}
