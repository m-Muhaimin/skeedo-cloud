<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Api\Ai;

use Ai\Application\Commands\CreateConversationCommand;
use Ai\Domain\Entities\ConversationEntity;
use Ai\Domain\Exceptions\InsufficientCreditsException;
use Easy\Http\Message\RequestMethod;
use Easy\Http\Message\StatusCode;
use Easy\Router\Attributes\Path;
use Easy\Router\Attributes\Route;
use Presentation\Exceptions\HttpException;
use Presentation\Resources\Api\ConversationResource;
use Presentation\Response\JsonResponse;
use Presentation\Validation\Validator;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use User\Domain\Entities\UserEntity;
use Workspace\Domain\Entities\WorkspaceEntity;

#[Path(prefix: '/conversations')]
#[Route(path: '/', method: RequestMethod::POST)]
class ConversationApi extends AiServicesApi implements
    RequestHandlerInterface
{
    public function __construct(
        private Validator $validator,
        private Dispatcher $dispatcher,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var UserEntity */
        $user = $request->getAttribute(UserEntity::class);

        /** @var WorkspaceEntity */
        $ws = $request->getAttribute(WorkspaceEntity::class);

        $cmd = new CreateConversationCommand(
            $ws,
            $user
        );

        try {
            /** @var ConversationEntity */
            $conversation = $this->dispatcher->dispatch($cmd);
        } catch (InsufficientCreditsException $th) {
            throw new HttpException(
                'Insufficient credits',
                StatusCode::FORBIDDEN
            );
        }

        return new JsonResponse(
            new ConversationResource($conversation, ['messages'])
        );
    }
}
