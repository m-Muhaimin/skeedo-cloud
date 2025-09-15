<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin\Api\Assistants;

use Assistant\Application\Commands\ReadAssistantCommand;
use Assistant\Domain\Entities\AssistantEntity;
use Assistant\Domain\Exceptions\AssistantNotFoundException;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Presentation\Exceptions\NotFoundException;
use Presentation\Resources\Admin\Api\AssistantResource;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Presentation\Response\JsonResponse;

#[Route(path: '/[uuid:id]', method: RequestMethod::GET)]
class ReadAssistantRequestHandler extends AssistantsApi
implements RequestHandlerInterface
{

    public function __construct(
        private Dispatcher $dispatcher
    ) {
    }


    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $id = $request->getAttribute('id');

        try {
            $cmd = new ReadAssistantCommand($id);

            /** @var AssistantEntity */
            $assistant = $this->dispatcher->dispatch($cmd);
        } catch (AssistantNotFoundException $th) {
            throw new NotFoundException(
                param: 'id',
                previous: $th
            );
        }

        return new JsonResponse(new AssistantResource($assistant));
    }
}
