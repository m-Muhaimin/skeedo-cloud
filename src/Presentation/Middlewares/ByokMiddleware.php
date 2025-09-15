<?php

declare(strict_types=1);

namespace Presentation\Middlewares;

use Application;
use Easy\Container\Attributes\Inject;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\Config\WorkspaceKey;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Workspace\Domain\Entities\WorkspaceEntity;

class ByokMiddleware implements MiddlewareInterface
{
    public function __construct(
        private Application $app,
        private Dispatcher $dispatcher,

        #[Inject('option.openai.custom_keys_enabled')]
        private bool $isOpenAiCustomKeysEnabled = false,

        #[Inject('option.anthropic.custom_keys_enabled')]
        private bool $isAnthropicCustomKeysEnabled = false,

        #[Inject('option.features.api.is_enabled')]
        private ?bool $isApiEnabled = null,

        #[Inject('option.features.admin_api.is_enabled')]
        private ?bool $isAdminApiEnabled = null,
    ) {}

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $workspace = $request->getAttribute(WorkspaceEntity::class);

        if (
            $workspace
            && $workspace->getOpenaiApiKey()->value
            && $this->isOpenAiCustomKeysEnabled
        ) {
            $this->app
                ->set(
                    WorkspaceKey::OpenAI,
                    $workspace->getOpenaiApiKey()->value
                );
        }

        if (
            $workspace
            && $workspace->getAnthropicApiKey()->value
            && $this->isAnthropicCustomKeysEnabled
        ) {
            $this->app
                ->set(
                    WorkspaceKey::Anthropic,
                    $workspace->getAnthropicApiKey()->value
                );
        }

        return $handler->handle($request);
    }
}
