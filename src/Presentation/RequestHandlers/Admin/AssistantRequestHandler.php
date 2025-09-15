<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin;

use Assistant\Application\Commands\ReadAssistantCommand;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Presentation\Resources\Admin\Api\AssistantResource;
use Presentation\Response\RedirectResponse;
use Presentation\Response\ViewResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use User\Domain\Exceptions\UserNotFoundException;

#[Route(path: '/assistants/[uuid:id]', method: RequestMethod::GET)]
#[Route(path: '/assistants/[uuid:id]/[dataset:view]', method: RequestMethod::GET)]
#[Route(path: '/assistants/new', method: RequestMethod::GET)]
class AssistantRequestHandler extends AbstractAdminViewRequestHandler implements
    RequestHandlerInterface
{
    public function __construct(
        private Dispatcher $dispatcher
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $id = $request->getAttribute('id');

        $data = [];

        if ($id) {
            $cmd = new ReadAssistantCommand($id);

            try {
                $assistant = $this->dispatcher->dispatch($cmd);
            } catch (UserNotFoundException $th) {
                return new RedirectResponse('/admin/users');
            }

            $extend = [];

            if ($request->getAttribute('view') === 'dataset') {
                $extend[] = 'dataset';
            }

            $data['assistant'] = new AssistantResource($assistant, $extend);
        }

        $view = $request->getAttribute('view')
            ? 'assistant-' . $request->getAttribute('view')
            : 'assistant';

        return new ViewResponse(
            '/templates/admin/' . $view . '.twig',
            $data
        );
    }
}
