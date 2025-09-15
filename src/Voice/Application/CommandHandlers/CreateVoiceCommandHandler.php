<?php

declare(strict_types=1);

namespace Voice\Application\CommandHandlers;

use Ai\Domain\Services\AiServiceFactoryInterface;
use Ai\Domain\Speech\VoiceCloningServiceInterface;
use RuntimeException;
use Shared\Domain\ValueObjects\Id;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Shared\Infrastructure\CommandBus\Exception\NoHandlerFoundException;
use User\Application\Commands\ReadUserCommand;
use Voice\Application\Commands\CreateVoiceCommand;
use Voice\Domain\Entities\VoiceEntity;
use Voice\Domain\VoiceRepositoyInterface;
use Workspace\Application\Commands\ReadWorkspaceCommand;
use Workspace\Domain\Entities\WorkspaceEntity;

class CreateVoiceCommandHandler
{
    public function __construct(
        private VoiceRepositoyInterface $repo,
        private AiServiceFactoryInterface $factory,
        private Dispatcher $dispatcher,
    ) {}

    /**
     * @throws NoHandlerFoundException
     * @throws RuntimeException
     */
    public function handle(CreateVoiceCommand $cmd): VoiceEntity
    {
        $ws = $cmd->workspace;
        $user = $cmd->user;

        if ($ws instanceof Id) {
            /** @var WorkspaceEntity */
            $ws = $this->dispatcher->dispatch(new ReadWorkspaceCommand($ws));
        }

        if ($user instanceof Id) {
            /** @var UserEntity */
            $user = $this->dispatcher->dispatch(new ReadUserCommand($user));
        }

        $service = $this->factory->create(
            VoiceCloningServiceInterface::class,
            $cmd->model
        );

        $voice = $service->cloneVoice(
            $cmd->name->value,
            $cmd->file->getStream(),
            $user,
        );

        $voice->setOwner($ws, $user);
        $voice->setVisibility($cmd->visibility);

        $this->repo->add($voice);

        return $voice;
    }
}
