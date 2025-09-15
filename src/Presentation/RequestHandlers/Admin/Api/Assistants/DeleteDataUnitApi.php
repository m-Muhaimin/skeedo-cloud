<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin\Api\Assistants;

use Assistant\Application\Commands\DeleteDataUnitCommand;
use Assistant\Domain\Exceptions\AssistantNotFoundException;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Override;
use Presentation\Validation\Validator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Dataset\Domain\Entities\AbstractDataUnitEntity;
use Presentation\Exceptions\NotFoundException;
use Presentation\Response\EmptyResponse;
use Shared\Infrastructure\CommandBus\Dispatcher;

#[Route(path: '/[uuid:aid]/dataset/[uuid:id]', method: RequestMethod::DELETE)]
class DeleteDataUnitApi extends AssistantsApi implements
    RequestHandlerInterface
{
    public function __construct(
        private Validator $validator,
        private Dispatcher $dispatcher
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $cmd = new DeleteDataUnitCommand(
            $request->getAttribute('aid'),
            $request->getAttribute('id')
        );

        try {
            /** @var AbstractDataUnitEntity */
            $this->dispatcher->dispatch($cmd);
        } catch (AssistantNotFoundException $th) {
            throw new NotFoundException(
                param: 'id',
                previous: $th
            );
        }

        return new EmptyResponse();
    }
}
