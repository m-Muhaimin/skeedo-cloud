<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Api\Ai;

use Ai\Application\Commands\GenerateCodeDocumentCommand;
use Ai\Domain\Entities\CodeDocumentEntity;
use Ai\Domain\ValueObjects\Chunk;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Generator;
use Presentation\EventStream\Streamer;
use Presentation\Http\Message\CallbackStream;
use Presentation\Resources\Api\CodeDocumentResource;
use Presentation\Response\Response;
use Presentation\Validation\Validator;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use User\Domain\Entities\UserEntity;
use Workspace\Domain\Entities\WorkspaceEntity;

#[Route(path: '/completions/code', method: RequestMethod::POST)]
class CodeCompletionsApi extends AiServicesApi implements
    RequestHandlerInterface
{
    public function __construct(
        private Validator $validator,
        private Dispatcher $dispatcher,
        private Streamer $streamer
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->validateRequest($request);

        /** @var UserEntity */
        $user = $request->getAttribute(UserEntity::class);

        /** @var WorkspaceEntity */
        $ws = $request->getAttribute(WorkspaceEntity::class);

        $params = (array) $request->getParsedBody();

        $cmd = new GenerateCodeDocumentCommand(
            $ws,
            $user,
            $ws->getSubscription() ? $ws->getSubscription()->getPlan()->getConfig()->coder->model : 'gpt-3.5-turbo',
            $params['prompt'],
            $params['language'],
        );

        $cmd->params = $params;

        /** @var Generator<int,Chunk,null,CodeDocumentEntity> */
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

        /** @var CodeDocumentEntity */
        $doc = $generator->getReturn();
        $this->streamer->sendEvent('document', new CodeDocumentResource($doc));
    }

    private function validateRequest(ServerRequestInterface $req): void
    {
        $this->validator->validateRequest($req, [
            'prompt' => 'required|string',
            'language' => 'required|string',
        ]);
    }
}
