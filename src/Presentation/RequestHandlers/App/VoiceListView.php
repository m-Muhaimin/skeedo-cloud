<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\App;

use Easy\Container\Attributes\Inject;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Presentation\Response\RedirectResponse;
use Presentation\Response\ViewResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Workspace\Domain\Entities\WorkspaceEntity;

#[Route(path: '/voiceover/voices', method: RequestMethod::GET)]
class VoiceListView extends AppView implements
    RequestHandlerInterface
{
    public function __construct(
        #[Inject('option.features.voiceover.is_enabled')]
        private bool $isEnabled = false,

        #[Inject('option.features.voiceover.is_voice_cloning_enabled')]
        private bool $isVoiceCloningEnabled = false
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->isEnabled) {
            return new RedirectResponse('/app');
        }

        /** @var WorkspaceEntity */
        $ws = $request->getAttribute(WorkspaceEntity::class);

        $features = [
            'voice_cloning' => $this->isVoiceCloningEnabled
                && !$ws->isVoiceCapExceeded(),
        ];

        return new ViewResponse(
            '/templates/app/voices.twig',
            [
                'features' => $features,
            ]
        );
    }
}
