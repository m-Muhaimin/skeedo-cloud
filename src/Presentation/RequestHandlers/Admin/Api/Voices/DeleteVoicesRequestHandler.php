<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin\Api\Voices;

use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Override;
use Presentation\Exceptions\NotFoundException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Presentation\Response\EmptyResponse;
use Voice\Application\Commands\DeleteVoiceCommand;
use Voice\Domain\Exceptions\VoiceNotFoundException;

#[Route(path: '/[uuid:id]', method: RequestMethod::DELETE)]
class DeleteVoicesRequestHandler extends VoiceApi implements
    RequestHandlerInterface
{
    public function __construct(
        private Dispatcher $dispatcher
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $id = $request->getAttribute('id');

        $cmd = new DeleteVoiceCommand($id);

        try {
            $this->dispatcher->dispatch($cmd);
        } catch (VoiceNotFoundException $th) {
            throw new NotFoundException(
                param: 'id',
                previous: $th
            );
        }

        return new EmptyResponse();
    }
}
