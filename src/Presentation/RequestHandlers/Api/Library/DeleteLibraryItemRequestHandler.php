<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Api\Library;

use Ai\Application\Commands\DeleteLibraryItemCommand;
use Ai\Domain\Exceptions\LibraryItemNotFoundException;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Middleware;
use Easy\Router\Attributes\Route;
use LogicException;
use Presentation\AccessControls\LibraryItemAccessControl;
use Presentation\AccessControls\Permission;
use Presentation\Exceptions\HttpException;
use Presentation\Exceptions\NotFoundException;
use Presentation\Middlewares\DemoEnvironmentMiddleware;
use Presentation\Response\EmptyResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Shared\Infrastructure\CommandBus\Exception\NoHandlerFoundException;
use User\Domain\Entities\UserEntity;

#[Middleware(DemoEnvironmentMiddleware::class)]
#[Route(path: '/[uuid:id]', method: RequestMethod::DELETE)]
class DeleteLibraryItemRequestHandler extends LibraryApi implements
    RequestHandlerInterface
{
    public function __construct(
        private LibraryItemAccessControl $ac,
        private Dispatcher $dispatcher
    ) {
    }

    /**
     * @throws NotFoundException
     * @throws NoHandlerFoundException
     * @throws LogicException
     * @throws HttpException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->validateRequest($request);

        $cmd = new DeleteLibraryItemCommand($request->getAttribute("id"));

        try {
            $this->dispatcher->dispatch($cmd);
        } catch (LibraryItemNotFoundException $th) {
            throw new NotFoundException(previous: $th);
        }

        return new EmptyResponse();
    }

    private function validateRequest(ServerRequestInterface $request): void
    {
        /** @var UserEntity */
        $user = $request->getAttribute(UserEntity::class);

        $this->ac->denyUnlessGranted(
            Permission::LIBRARY_ITEM_DELETE,
            $user,
            $request->getAttribute("id")
        );
    }
}
