<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Api;

use Ai\Application\Commands\ListLibraryItemsCommand;
use Ai\Domain\Entities\AbstractLibraryItemEntity;
use Ai\Domain\Entities\ClassificationEntity;
use Ai\Domain\Entities\CodeDocumentEntity;
use Ai\Domain\Entities\ConversationEntity;
use Ai\Domain\Entities\DocumentEntity;
use Ai\Domain\Entities\ImageEntity;
use Ai\Domain\Entities\IsolatedVoiceEntity;
use Ai\Domain\Entities\MemoryEntity;
use Ai\Domain\Entities\SpeechEntity;
use Ai\Domain\Entities\TranscriptionEntity;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Override;
use Presentation\Resources\Api\ClassificationResource;
use Presentation\Resources\Api\CodeDocumentResource;
use Presentation\Resources\Api\ConversationResource;
use Presentation\Resources\Api\DocumentResource;
use Presentation\Resources\Api\ImageResource;
use Presentation\Resources\Api\IsolatedVoiceResource;
use Presentation\Resources\Api\MemoryResource;
use Presentation\Resources\Api\PresetResource;
use Presentation\Resources\Api\SpeechResource;
use Presentation\Resources\Api\TranscriptionResource;
use Presentation\Resources\ListResource;
use Presentation\Response\JsonResponse;
use Preset\Application\Commands\ListPresetsCommand;
use Preset\Domain\Entities\PresetEntity;
use Preset\Domain\ValueObjects\Status;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Traversable;
use User\Domain\Entities\UserEntity;
use Workspace\Domain\Entities\WorkspaceEntity;

#[Route(path: '/search', method: RequestMethod::GET)]
class SearchRequestHandler extends Api implements RequestHandlerInterface
{
    public function __construct(
        private Dispatcher $dispatcher
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var UserEntity */
        $user = $request->getAttribute(UserEntity::class);

        /** @var WorkspaceEntity */
        $workspace = $request->getAttribute(WorkspaceEntity::class);

        $list = new ListResource();

        $params = (object) $request->getQueryParams();
        $query = $params->query ?? null;
        $limit = $params->limit ?? null;

        $this
            ->searchDocuments($list, $user, $workspace, $query, $limit)
            ->searchPresets($list, $query, $limit);

        return new JsonResponse($list);
    }

    private function searchPresets(
        ListResource $list,
        ?string $query,
        ?int $limit
    ): self {
        $cmd = new ListPresetsCommand();
        $cmd->status = Status::from(1);
        $cmd->setLimit(5);

        if ($query) {
            $cmd->query = $query;
        }

        if ($limit) {
            $cmd->setLimit($limit);
        }

        /** @var Traversable<int,PresetEntity> $presets */
        $presets = $this->dispatcher->dispatch($cmd);

        foreach ($presets as $preset) {
            $list->pushData(new PresetResource($preset));
        }

        return $this;
    }

    private function searchDocuments(
        ListResource $list,
        UserEntity $user,
        WorkspaceEntity $workspace,
        ?string $query,
        ?int $limit
    ): self {
        $cmd = new ListLibraryItemsCommand();
        $cmd->user = $user;
        $cmd->workspace = $workspace;

        if ($query) {
            $cmd->query = $query;
        }

        if ($limit) {
            $cmd->setLimit($limit);
        }

        /** @var Iterator<int,AbstractLibraryItemEntity> $items */
        $items = $this->dispatcher->dispatch($cmd);
        foreach ($items as $item) {
            match (true) {
                $item instanceof ImageEntity =>
                $list->pushData(new ImageResource($item)),

                $item instanceof DocumentEntity =>
                $list->pushData(new DocumentResource($item)),

                $item instanceof CodeDocumentEntity =>
                $list->pushData(new CodeDocumentResource($item)),

                $item instanceof TranscriptionEntity =>
                $list->pushData(new TranscriptionResource($item)),

                $item instanceof SpeechEntity =>
                $list->pushData(new SpeechResource($item)),

                $item instanceof ConversationEntity =>
                $list->pushData(new ConversationResource($item)),

                $item instanceof IsolatedVoiceEntity =>
                $list->pushData(new IsolatedVoiceResource($item)),

                $item instanceof ClassificationEntity =>
                $list->pushData(new ClassificationResource($item)),

                $item instanceof MemoryEntity =>
                $list->pushData(new MemoryResource($item)),

                default => null,
            };
        }
        return $this;
    }
}
