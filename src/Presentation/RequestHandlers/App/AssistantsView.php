<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\App;

use Assistant\Application\Commands\CountAssistantsCommand;
use Assistant\Domain\ValueObjects\Status;
use Easy\Container\Attributes\Inject;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Presentation\Response\RedirectResponse;
use Presentation\Response\ViewResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;

#[Route(path: '/assistants', method: RequestMethod::GET)]
class AssistantsView extends AppView implements
    RequestHandlerInterface
{
    public function __construct(
        private Dispatcher $dispatcher,

        #[Inject('option.features.chat.is_enabled')]
        private bool $isEnabled = false
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->isEnabled) {
            return new RedirectResponse('/app');
        }

        $cmd = new CountAssistantsCommand();
        $cmd->status = Status::ACTIVE;
        $count = $this->dispatcher->dispatch($cmd);

        if ($count == 0) {
            return new RedirectResponse('/app/chat');
        }

        return new ViewResponse(
            '/templates/app/assistants.twig',
        );
    }
}
