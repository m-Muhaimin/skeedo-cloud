<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Api\Ai;

use Ai\Application\Commands\GenerateMessageCommand;
use Ai\Domain\Entities\MessageEntity;
use Ai\Domain\ValueObjects\Chunk;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Generator;
use Presentation\EventStream\Streamer;
use Presentation\Http\Message\CallbackStream;
use Presentation\Response\Response;
use Presentation\Validation\Validator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use User\Domain\Entities\UserEntity;
use Workspace\Domain\Entities\WorkspaceEntity;

#[Route(path: '/[uuid:cid]/messages', method: RequestMethod::POST)]
class MessageApi extends ConversationApi implements
    RequestHandlerInterface
{
    public function __construct(
        private Validator $validator,
        private Dispatcher $dispatcher,
        private Streamer $streamer
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->validateRequest($request);

        /** @var UserEntity */
        $user = $request->getAttribute(UserEntity::class);

        /** @var WorkspaceEntity */
        $ws = $request->getAttribute(WorkspaceEntity::class);

        $payload = $request->getParsedBody();

        $cmd = new GenerateMessageCommand(
            $ws,
            $user,
            $request->getAttribute('cid'),
            $payload->model
        );

        if (property_exists($payload, 'content')) {
            $cmd->setPrompt($payload->content);
        }

        if (property_exists($payload, 'quote')) {
            $cmd->setQuote($payload->quote);
        }

        if (property_exists($payload, 'assistant_id')) {
            $cmd->setAssistant($payload->assistant_id);
        }

        if (property_exists($payload, 'parent_id')) {
            $cmd->setParent($payload->parent_id);
        }

        /** @var UploadedFileInterface */
        $file = $request->getUploadedFiles()['file'] ?? null;

        if ($file) {
            $cmd->file = $file;
        }

        /** @var Generator<int,Chunk|MessageEntity> */
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

    /**
     * @param Generator<int,Chunk|MessageEntity> $generator
     */
    private function callback(Generator $generator)
    {
        $this->streamer->stream($generator);
    }

    private function validateRequest(ServerRequestInterface $req): void
    {
        $this->validator->validateRequest($req, [
            'model' => 'required|string',
            'content' => 'required_without:parent_id|string',
            'quote' => 'string',
            'assistant_id' => 'sometimes|uuid',
            'parent_id' => 'required_without:content|sometimes|uuid',
            'file' => 'sometimes|uploaded_file',
        ]);
    }
}
