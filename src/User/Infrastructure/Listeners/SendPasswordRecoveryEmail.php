<?php

declare(strict_types=1);

namespace User\Infrastructure\Listeners;

use Laminas\Diactoros\ServerRequestFactory;
use Presentation\Resources\Api\UserResource;
use Shared\Infrastructure\Email\EmailService;
use User\Domain\Events\PasswordRecoveryCreatedEvent;

/** 
 * Class SendPasswordRecoveryEmail
 *
 * This class listens to the event of password recovery creation. 
 * When triggered, it will send a password recovery email to the user.
 */
class SendPasswordRecoveryEmail
{
    /**
     * Constructs an instance of this listener.
     *
     * Injects necessary services and configuration values.
     *
     * @param EmailService $service The email service to send emails
     */
    public function __construct(
        private EmailService $service
    ) {}

    /**
     * Invoked when a PasswordRecoveryCreatedEvent is dispatched.
     *
     * This listener responds to the event by sending an email to the user with 
     * information on how to recover their password.
     *
     * @param PasswordRecoveryCreatedEvent $event The password recovery creation 
     * event
     */
    public function __invoke(PasswordRecoveryCreatedEvent $event)
    {
        $user = $event->user;

        // Create reset URL from the User ID and Recovery Token
        $path = '/recovery'
            . '/' . (string) $user->getId()->getValue()
            . '/' . $user->getRecoveryToken()->value;

        $req = ServerRequestFactory::fromGlobals();
        $uri = $req->getUri()
            ->withPath($path)
            ->withQuery('')
            ->withFragment('');

        // Prepare data for the email template
        $data = [
            'user' => new UserResource($user),
            'reset_url' => (string) $uri,
            'locale' => $user->getLanguage()->value,
        ];

        $this->service->sendTemplate(
            $user->getEmail()->value,
            '@emails/password-reset.twig',
            $data
        );
    }
}
