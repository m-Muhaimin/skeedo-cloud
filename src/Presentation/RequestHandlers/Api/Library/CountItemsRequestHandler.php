<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Api\Library;

use Ai\Application\Commands\CountLibraryItemsCommand;
use Ai\Domain\ValueObjects\ItemType;
use Category\Domain\Exceptions\CategoryNotFoundException;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Presentation\Resources\CountResource;
use Presentation\Response\JsonResponse;
use Presentation\Validation\ValidationException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use User\Domain\Entities\UserEntity;
use Workspace\Domain\Entities\WorkspaceEntity;

#[Route(path: '/count', method: RequestMethod::GET)]
class CountItemsRequestHandler extends LibraryApi implements
    RequestHandlerInterface
{
    public function __construct(
        private Dispatcher $dispatcher
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var UserEntity */
        $user = $request->getAttribute(UserEntity::class);
        /** @var WorkspaceEntity */
        $workspace = $request->getAttribute(WorkspaceEntity::class);
        $type = $request->getAttribute('type');
        $params = (object) $request->getQueryParams();

        $cmd = new CountLibraryItemsCommand();
        $cmd->user = $user;
        $cmd->workspace = $workspace;

        match ($type) {
            'images' => $cmd->type = ItemType::IMAGE,
            'videos' => $cmd->type = ItemType::VIDEO,
            'documents' => $cmd->type = ItemType::DOCUMENT,
            'code-documents' => $cmd->type = ItemType::CODE_DOCUMENT,
            'transcriptions' => $cmd->type = ItemType::TRANSCRIPTION,
            'speeches' => $cmd->type = ItemType::SPEECH,
            'conversations' => $cmd->type = ItemType::CONVERSATION,
            'isolated-voices' => $cmd->type = ItemType::ISOLATED_VOICE,
            'classifications' => $cmd->type = ItemType::CLASSIFICATION,
            'compositions' => $cmd->type = ItemType::COMPOSITION,
            'memories' => $cmd->type = ItemType::MEMORY,
            default => null,
        };

        if (property_exists($params, 'query')) {
            $cmd->query = $params->query;
        }

        if (property_exists($params, 'model')) {
            $cmd->setModel($params->model);
        }

        try {
            /** @var int */
            $count = $this->dispatcher->dispatch($cmd);
        } catch (CategoryNotFoundException $th) {
            throw new ValidationException(
                'Invalid cursor',
                property_exists($params, 'starting_after')
                    ? 'starting_after'
                    : 'ending_before',
                previous: $th
            );
        }

        return new JsonResponse(new CountResource($count));
    }
}
