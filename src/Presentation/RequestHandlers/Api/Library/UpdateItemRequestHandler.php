<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Api\Library;

use Ai\Application\Commands\UpdateLibraryItemCommand;
use Ai\Domain\Entities\AbstractLibraryItemEntity;
use Ai\Domain\Entities\ClassificationEntity;
use Ai\Domain\Entities\CodeDocumentEntity;
use Ai\Domain\Entities\CompositionEntity;
use Ai\Domain\Entities\ConversationEntity;
use Ai\Domain\Entities\ImageEntity;
use Ai\Domain\Entities\IsolatedVoiceEntity;
use Ai\Domain\Entities\MemoryEntity;
use Ai\Domain\Entities\SpeechEntity;
use Ai\Domain\Entities\TranscriptionEntity;
use Ai\Domain\Entities\VideoEntity;
use Ai\Domain\Exceptions\LibraryItemNotFoundException;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Middleware;
use Easy\Router\Attributes\Route;
use Presentation\AccessControls\LibraryItemAccessControl;
use Presentation\AccessControls\Permission;
use Presentation\Exceptions\NotFoundException;
use Presentation\Middlewares\DemoEnvironmentMiddleware;
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
use Presentation\Response\JsonResponse;
use Presentation\Validation\Validator;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use User\Domain\Entities\UserEntity;

#[Middleware(DemoEnvironmentMiddleware::class)]
#[Route(path: '/[uuid:id]', method: RequestMethod::PATCH)]
#[Route(path: '/[uuid:id]', method: RequestMethod::POST)]
class UpdateItemRequestHandler extends LibraryApi implements
    RequestHandlerInterface
{
    public function __construct(
        private LibraryItemAccessControl $ac,
        private Validator $validator,
        private Dispatcher $dispatcher
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->validateRequest($request);

        $payload = (object) $request->getParsedBody();

        $cmd = new UpdateLibraryItemCommand(
            $request->getAttribute('id'),
        );

        if (property_exists($payload, 'title')) {
            $cmd->setTitle($payload->title);
        }

        if (property_exists($payload, 'content')) {
            $cmd->setContent($payload->content);
        }

        if (property_exists($payload, 'visibility')) {
            $cmd->setVisibility($payload->visibility);
        }

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
            $item instanceof MemoryEntity => $resource = new MemoryResource($item),
            default => $resource = new DocumentResource($item),
        };

        return new JsonResponse($resource);
    }

    private function validateRequest(ServerRequestInterface $req): void
    {
        $this->validator->validateRequest($req, [
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'visibility' => 'integer|in:0,1'
        ]);

        /** @var UserEntity */
        $user = $req->getAttribute(UserEntity::class);

        $this->ac->denyUnlessGranted(
            Permission::LIBRARY_ITEM_EDIT,
            $user,
            $req->getAttribute("id")
        );
    }
}
