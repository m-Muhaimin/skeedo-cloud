<?php

declare(strict_types=1);

namespace Workspace\Infrastructure\Listeners;

use Laminas\Diactoros\ServerRequestFactory;
use Presentation\Resources\Api\WorkspaceResource;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Shared\Infrastructure\Email\EmailService;
use Throwable;
use User\Application\Commands\ReadUserCommand;
use User\Domain\Entities\UserEntity;
use Workspace\Domain\Events\InvitationCreatedEvent;

class SendInvitationEmail
{
    public function __construct(
        private EmailService $service,
        private Dispatcher $dispatcher,
    ) {}

    public function __invoke(InvitationCreatedEvent $event)
    {

        $inv = $event->invitation;
        $ws = $inv->getWorkspace();

        // Create accept invitation url 
        $path = '/app/workspace'
            . '/' . (string) $ws->getId()->getValue()
            . '/invitations'
            . '/' . $inv->getId()->getValue();

        $req = ServerRequestFactory::fromGlobals();
        $uri = $req->getUri()
            ->withPath($path)
            ->withQuery('')
            ->withFragment('');

        $data = [
            'email' => $inv->getEmail()->value,
            'workspace' => new WorkspaceResource($ws),
            'accept_invitation_url' => (string) $uri,
        ];

        $user = $this->getUser($inv->getEmail()->value);
        if ($user) {
            $data['locale'] = $user->getLanguage()->value;
        }

        $this->service->sendTemplate(
            $inv->getEmail()->value,
            '@emails/workspace-invitation.twig',
            $data
        );
    }

    private function getUser(string $email): ?UserEntity
    {
        try {
            $cmd = new ReadUserCommand($email);
            return $this->dispatcher->dispatch($cmd);
        } catch (Throwable $th) {
            //throw $th;
        }

        return null;
    }
}
