<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\App\Workspace;

use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Presentation\Response\RedirectResponse;
use Presentation\Response\ViewResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use User\Domain\Entities\UserEntity;
use Workspace\Application\Commands\AcceptInvitationCommand;
use Workspace\Domain\Exceptions\InvitationNotFoundException;
use Workspace\Domain\Exceptions\WorkspaceNotFoundException;

#[Route(path: '/[uuid:id]/invitations/[uuid:iid]', method: RequestMethod::GET)]
class InvitationView extends WorkspaceView implements
    RequestHandlerInterface
{
    public function __construct(
        private Dispatcher $dispatcher,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var UserEntity */
        $user = $request->getAttribute(UserEntity::class);

        $cmd = new AcceptInvitationCommand(
            $user,
            $request->getAttribute("id"),
            $request->getAttribute("iid")
        );

        try {
            $this->dispatcher->dispatch($cmd);
        } catch (WorkspaceNotFoundException | InvitationNotFoundException $th) {
            return new RedirectResponse("/app/workspace");
        }

        return new ViewResponse(
            '/templates/app/workspace/overview.twig',
            ['show_invitation_accepted_toast' => true]
        );
    }
}
