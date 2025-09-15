<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Api\Ai;

use Ai\Application\Commands\GenerateDocumentCommand;
use Ai\Domain\Entities\DocumentEntity;
use Ai\Domain\ValueObjects\Chunk;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Generator;
use Presentation\EventStream\Streamer;
use Presentation\Http\Message\CallbackStream;
use Presentation\Resources\Api\DocumentResource;
use Presentation\Response\Response;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use User\Domain\Entities\UserEntity;
use Workspace\Domain\Entities\WorkspaceEntity;

#[Route(path: '/completions/[uuid:id]?', method: RequestMethod::POST)]
class CompletionsApi extends AiServicesApi implements
    RequestHandlerInterface
{
    public function __construct(
        private Dispatcher $dispatcher,
        private Streamer $streamer
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var UserEntity */
        $user = $request->getAttribute(UserEntity::class);

        /** @var WorkspaceEntity */
        $ws = $request->getAttribute(WorkspaceEntity::class);

        $params = (array) $request->getParsedBody();

        $cmd = new GenerateDocumentCommand(
            $ws,
            $user,
            $ws->getSubscription() ? $ws->getSubscription()->getPlan()->getConfig()->writer->model : 'gpt-3.5-turbo',
            $request->getAttribute('id') ?? $params['query'] ?? ''
        );

        $cmd->params = $params;

        /** @var Generator<int,Chunk,null,DocumentEntity> */
        $generator = $this->dispatcher->dispatch($cmd);

        $resp = (new Response())
            ->withHeader('Content-Type', 'text/event-stream')
            ->withHeader('Cache-Control', 'no-cache')
            ->withHeader('Connection', 'keep-alive')

            // Disable buffering for nginx servers to allow for streaming
            // This is required for the event stream to work
            ->withHeader('X-Accel-Buffering', 'no');

        $stream = new CallbackStream(
            $this->callback(...),
            $generator
        );

        return $resp->withBody($stream);
    }

    private function callback(Generator $generator)
    {
        $this->streamer->stream($generator);

        /** @var DocumentEntity */
        $doc = $generator->getReturn();
        $this->streamer->sendEvent('document', new DocumentResource($doc));
    }
}
