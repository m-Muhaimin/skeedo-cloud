<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Api\Library;

use Ai\Application\Commands\ReadLibraryItemCommand;
use Ai\Domain\Entities\AbstractLibraryItemEntity;
use Ai\Domain\Entities\ClassificationEntity;
use Ai\Domain\Entities\CodeDocumentEntity;
use Ai\Domain\Entities\CompositionEntity;
use Ai\Domain\Entities\ConversationEntity;
use Ai\Domain\Entities\ImageEntity;
use Ai\Domain\Entities\IsolatedVoiceEntity;
use Ai\Domain\Entities\SpeechEntity;
use Ai\Domain\Entities\TranscriptionEntity;
use Ai\Domain\Entities\VideoEntity;
use Ai\Domain\Exceptions\LibraryItemNotFoundException;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Presentation\AccessControls\LibraryItemAccessControl;
use Presentation\Exceptions\NotFoundException;
use Presentation\Resources\Api\ClassificationResource;
use Presentation\Resources\Api\CodeDocumentResource;
use Presentation\Resources\Api\CompositionResource;
use Presentation\Resources\Api\ConversationResource;
use Presentation\Resources\Api\DocumentResource;
use Presentation\Resources\Api\ImageResource;
use Presentation\Resources\Api\IsolatedVoiceResource;
use Presentation\Resources\Api\SpeechResource;
use Presentation\Resources\Api\TranscriptionResource;
use Presentation\Resources\Api\VideoResource;
use Presentation\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;

#[Route(path: '/[uuid:id]', method: RequestMethod::GET)]
class ReadItemRequestHandler extends LibraryApi implements
    RequestHandlerInterface
{
    public function __construct(
        private LibraryItemAccessControl $ac,
        private Dispatcher $dispatcher
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $cmd = new ReadLibraryItemCommand(
            $request->getAttribute('id'),
        );

        try {
            /** @var AbstractLibraryItemEntity */
            $item = $this->dispatcher->dispatch($cmd);
        } catch (LibraryItemNotFoundException $th) {
            throw new NotFoundException(
                param: 'id',
                previous: $th
            );
        }

        match (true) {
            $item instanceof CodeDocumentEntity => $resource = new CodeDocumentResource($item),
            $item instanceof TranscriptionEntity => $resource = new TranscriptionResource($item),
            $item instanceof SpeechEntity => $resource = new SpeechResource($item),
            $item instanceof ImageEntity => $resource = new ImageResource($item),
            $item instanceof VideoEntity => $resource = new VideoResource($item),
            $item instanceof ConversationEntity => $resource = new ConversationResource($item, ['messages']),
            $item instanceof IsolatedVoiceEntity => $resource = new IsolatedVoiceResource($item),
            $item instanceof ClassificationEntity => $resource = new ClassificationResource($item),
            $item instanceof CompositionEntity => $resource = new CompositionResource($item),
            default => $resource = new DocumentResource($item),
        };

        return new JsonResponse($resource);
    }
}
