<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\App;

use Ai\Application\Commands\ReadLibraryItemCommand;
use Ai\Domain\Entities\SpeechEntity;
use Ai\Domain\Exceptions\LibraryItemNotFoundException;
use Easy\Container\Attributes\Inject;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Presentation\AccessControls\LibraryItemAccessControl;
use Presentation\AccessControls\Permission;
use Presentation\Resources\Api\SpeechResource;
use Presentation\Resources\Api\VoiceResource;
use Presentation\Response\RedirectResponse;
use Presentation\Response\ViewResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use User\Domain\Entities\UserEntity;
use Voice\Application\Commands\ListVoicesCommand;
use Voice\Application\Commands\ReadVoiceCommand;
use Voice\Domain\Entities\VoiceEntity;
use Voice\Domain\Exceptions\VoiceNotFoundException;
use Voice\Domain\ValueObjects\Status;

#[Route(path: '/voiceover/[uuid:id]?', method: RequestMethod::GET)]
class VoiceoverView extends AppView implements
    RequestHandlerInterface
{
    public function __construct(
        private Dispatcher $dispatcher,
        private LibraryItemAccessControl $ac,

        #[Inject('option.features.voiceover.is_enabled')]
        private bool $isEnabled = false
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->isEnabled) {
            return new RedirectResponse('/app');
        }

        /** @var UserEntity */
        $user = $request->getAttribute(UserEntity::class);

        $id = $request->getAttribute('id');

        if (!$id) {
            return new RedirectResponse('/app/voiceover/voices');
        }

        $speech = null;
        $voice = null;

        // First check if the ID belongs to a document
        $cmd = new ReadLibraryItemCommand($id);

        try {
            /** @var SpeechEntity */
            $speech = $this->dispatcher->dispatch($cmd);

            if (
                !($speech instanceof SpeechEntity)
                || !$this->ac->isGranted(Permission::LIBRARY_ITEM_READ, $user, $speech)
            ) {
                return new RedirectResponse('/app/voiceover/voices');
            }

            $voice = $speech->getVoice();
        } catch (LibraryItemNotFoundException $th) {
            // Document not found, we'll check if it's a voice
            $speech = null;
        }

        if (!$speech) {
            // Couln't find a speech, let's check if it's a voice
            $cmd = new ReadVoiceCommand($id);

            try {
                /** @var VoiceEntity */
                $voice = $this->dispatcher->dispatch($cmd);
                // $data['voice'] = new VoiceResource($voice);
            } catch (VoiceNotFoundException $th) {
                return new RedirectResponse('/app/voiceover/voices');
            }
        }

        if (!$voice) {
            // Couldn't find a voice, get first voice
            $cmd = new ListVoicesCommand();
            $cmd->status = Status::ACTIVE;
            $cmd->setLimit(1);

            /** @var null|VoiceEntity */
            $voice = $this->dispatcher->dispatch($cmd)->current() ?? null;
        }

        $data = [
            'voice' => $voice ? new VoiceResource($voice) : null,
            'speech' => $speech ? new SpeechResource($speech) : null,
        ];

        return new ViewResponse(
            '/templates/app/voiceover.twig',
            $data
        );
    }
}
