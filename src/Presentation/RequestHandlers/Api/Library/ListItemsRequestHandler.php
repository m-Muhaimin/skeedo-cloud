<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Api\Library;

use Ai\Application\Commands\ListLibraryItemsCommand;
use Ai\Domain\Entities\AbstractLibraryItemEntity;
use Ai\Domain\Entities\ClassificationEntity;
use Ai\Domain\Entities\CodeDocumentEntity;
use Ai\Domain\Entities\CompositionEntity;
use Ai\Domain\Entities\ConversationEntity;
use Ai\Domain\Entities\DocumentEntity;
use Ai\Domain\Entities\ImageEntity;
use Ai\Domain\Entities\IsolatedVoiceEntity;
use Ai\Domain\Entities\MemoryEntity;
use Ai\Domain\Entities\SpeechEntity;
use Ai\Domain\Entities\TranscriptionEntity;
use Ai\Domain\Entities\VideoEntity;
use Ai\Domain\Exceptions\LibraryItemNotFoundException;
use Ai\Domain\ValueObjects\ItemType;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Iterator;
use Presentation\Resources\Api\ClassificationResource;
use Presentation\Resources\Api\CodeDocumentResource;
use Presentation\Resources\Api\CompositionResource;
use Presentation\Resources\Api\ConversationResource;
use Presentation\Resources\Api\DocumentResource;
use Presentation\Resources\Api\ImageResource;
use Presentation\Resources\Api\IsolatedVoiceResource;
use Presentation\Resources\Api\MemoryResource;
use Presentation\Resources\Api\SpeechResource;
use Presentation\Resources\Api\TranscriptionResource;
use Presentation\Resources\Api\VideoResource;
use Presentation\Resources\ListResource;
use Presentation\Response\JsonResponse;
use Presentation\Validation\ValidationException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use User\Domain\Entities\UserEntity;
use Workspace\Domain\Entities\WorkspaceEntity;

#[Route(path: '/', method: RequestMethod::GET)]
class ListItemsRequestHandler extends LibraryApi implements
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

        $cmd = new ListLibraryItemsCommand();
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

        if ($type === 'conversations') {
            $cmd->setOrderBy('updated_at', 'desc');
        }

        if (property_exists($params, 'model')) {
            $cmd->setModel($params->model);
        }

        if (property_exists($params, 'query')) {
            $cmd->query = $params->query;
        }

        if (property_exists($params, 'sort') && $params->sort) {
            $sort = explode(':', $params->sort);
            $orderBy = $sort[0];
            $dir = $sort[1] ?? 'asc';
            $cmd->setOrderBy($orderBy, $dir);
        }

        if (property_exists($params, 'starting_after') && $params->starting_after) {
            $cmd->setCursor(
                $params->starting_after,
                'starting_after'
            );
        } elseif (property_exists($params, 'ending_before') && $params->ending_before) {
            $cmd->setCursor(
                $params->ending_before,
                'ending_before'
            );
        }

        if (property_exists($params, 'limit')) {
            $cmd->setLimit((int) $params->limit);
        }

        try {
            /** @var Iterator<int,AbstractLibraryItemEntity> $items */
            $items = $this->dispatcher->dispatch($cmd);
        } catch (LibraryItemNotFoundException $th) {
            throw new ValidationException(
                'Invalid cursor',
                property_exists($params, 'starting_after')
                    ? 'starting_after'
                    : 'ending_before',
                previous: $th
            );
        }

        $res = new ListResource();
        foreach ($items as $item) {
            match (true) {
                $item instanceof ImageEntity =>
                $res->pushData(new ImageResource($item)),

                $item instanceof VideoEntity =>
                $res->pushData(new VideoResource($item)),

                $item instanceof DocumentEntity =>
                $res->pushData(new DocumentResource($item)),

                $item instanceof CodeDocumentEntity =>
                $res->pushData(new CodeDocumentResource($item)),

                $item instanceof TranscriptionEntity =>
                $res->pushData(new TranscriptionResource($item)),

                $item instanceof SpeechEntity =>
                $res->pushData(new SpeechResource($item)),

                $item instanceof ConversationEntity =>
                $res->pushData(new ConversationResource($item, ['messages'])),

                $item instanceof IsolatedVoiceEntity =>
                $res->pushData(new IsolatedVoiceResource($item)),

                $item instanceof ClassificationEntity =>
                $res->pushData(new ClassificationResource($item)),

                $item instanceof CompositionEntity =>
                $res->pushData(new CompositionResource($item)),

                $item instanceof MemoryEntity =>
                $res->pushData(new MemoryResource($item)),

                default => null,
            };
        }

        return new JsonResponse($res);
    }
}
