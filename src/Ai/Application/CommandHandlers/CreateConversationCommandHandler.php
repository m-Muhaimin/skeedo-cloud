<?php

declare(strict_types=1);

namespace Ai\Application\CommandHandlers;

use Ai\Application\Commands\CreateConversationCommand;
use Ai\Application\Commands\GenerateDocumentCommand;
use Ai\Domain\Exceptions\InsufficientCreditsException;
use Ai\Domain\Repositories\LibraryItemRepositoryInterface;
use Ai\Domain\Services\AiServiceFactoryInterface;
use Ai\Domain\Entities\ConversationEntity;
use Preset\Domain\Placeholder\ParserService;
use Preset\Domain\Repositories\PresetRepositoryInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use User\Domain\Entities\UserEntity;
use User\Domain\Exceptions\UserNotFoundException;
use User\Domain\Repositories\UserRepositoryInterface;
use Workspace\Domain\Entities\WorkspaceEntity;
use Workspace\Domain\Exceptions\WorkspaceNotFoundException;
use Workspace\Domain\Repositories\WorkspaceRepositoryInterface;

class CreateConversationCommandHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepo,
        private WorkspaceRepositoryInterface $wsRepo,
        private PresetRepositoryInterface $pRepo,
        private LibraryItemRepositoryInterface $repo,

        private ParserService $parser,
        private AiServiceFactoryInterface $factory,
        private EventDispatcherInterface $dispatcher,
    ) {
    }

    /**
     * @throws WorkspaceNotFoundException
     * @throws UserNotFoundException
     * @throws InsufficientCreditsException
     */
    public function handle(CreateConversationCommand $cmd): ConversationEntity
    {
        $ws = $cmd->workspace instanceof WorkspaceEntity
            ? $cmd->workspace
            : $this->wsRepo->ofId($cmd->workspace);

        $user = $cmd->user instanceof UserEntity
            ? $cmd->user
            : $this->userRepo->ofId($cmd->user);

        if (
            !is_null($ws->getTotalCreditCount()->value)
            && (float) $ws->getTotalCreditCount()->value <= 0
        ) {
            throw new InsufficientCreditsException();
        }

        $entity = new ConversationEntity(
            $ws,
            $user
        );

        $this->repo->add($entity);

        return $entity;
    }
}
