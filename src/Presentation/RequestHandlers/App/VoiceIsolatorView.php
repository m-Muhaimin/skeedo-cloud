<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\App;

use Ai\Application\Commands\ReadLibraryItemCommand;
use Ai\Domain\Entities\IsolatedVoiceEntity;
use Ai\Domain\Entities\SpeechEntity;
use Ai\Domain\Entities\TranscriptionEntity;
use Ai\Domain\Exceptions\LibraryItemNotFoundException;
use Easy\Container\Attributes\Inject;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Presentation\AccessControls\LibraryItemAccessControl;
use Presentation\AccessControls\Permission;
use Presentation\Resources\Api\IsolatedVoiceResource;
use Presentation\Resources\Api\SpeechResource;
use Presentation\Resources\Api\TranscriptionResource;
use Presentation\Response\RedirectResponse;
use Presentation\Response\ViewResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use User\Domain\Entities\UserEntity;

#[Route(path: '/voice-isolator/[uuid:id]?', method: RequestMethod::GET)]
class VoiceIsolatorView extends AppView implements
    RequestHandlerInterface
{
    public function __construct(
        private Dispatcher $dispatcher,
        private LibraryItemAccessControl $ac,

        #[Inject('option.features.voice_isolator.is_enabled')]
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
        $data = [];

        if ($id) {
            $cmd = new ReadLibraryItemCommand($id);

            try {
                $voice = $this->dispatcher->dispatch($cmd);

                if (
                    !($voice instanceof IsolatedVoiceEntity)
                    || !$this->ac->isGranted(Permission::LIBRARY_ITEM_READ, $user, $voice)
                ) {
                    return new RedirectResponse('/app/voice-isolator');
                }
            } catch (LibraryItemNotFoundException $th) {
                return new RedirectResponse('/app/voice-isolator');
            }

            $data['voice'] = new IsolatedVoiceResource($voice);
        }

        return new ViewResponse(
            '/templates/app/voice-isolator.twig',
            $data
        );
    }
}
