<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin;

use Assistant\Application\Commands\ReadAssistantCommand;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Presentation\Resources\Admin\Api\AssistantResource;
use Presentation\Resources\Admin\Api\VoiceResource;
use Presentation\Response\RedirectResponse;
use Presentation\Response\ViewResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use User\Domain\Exceptions\UserNotFoundException;
use Voice\Application\Commands\ReadVoiceCommand;
use Voice\Domain\Entities\VoiceEntity;

#[Route(path: '/voices/[uuid:id]', method: RequestMethod::GET)]
class VoiceView extends AbstractAdminViewRequestHandler implements
    RequestHandlerInterface
{
    public function __construct(
        private Dispatcher $dispatcher
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $id = $request->getAttribute('id');

        $cmd = new ReadVoiceCommand($id);

        try {
            /** @var VoiceEntity */
            $voice = $this->dispatcher->dispatch($cmd);
        } catch (UserNotFoundException $th) {
            return new RedirectResponse('/admin/voices');
        }

        return new ViewResponse(
            '/templates/admin/voice.twig',
            [
                'voice' => new VoiceResource($voice)
            ]
        );
    }
}
