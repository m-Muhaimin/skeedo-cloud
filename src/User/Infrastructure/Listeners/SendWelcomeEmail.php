<?php

declare(strict_types=1);

namespace User\Infrastructure\Listeners;

use Easy\Container\Attributes\Inject;
use Presentation\Resources\Api\UserResource;
use Shared\Infrastructure\Email\EmailService;
use User\Domain\Events\EmailVerifiedEvent;
use User\Domain\Events\UserCreatedEvent;

class SendWelcomeEmail
{
    /**
     * Constructs an instance of this listener.
     *
     * Injects necessary services and configuration values.
     *
     * @param EmailService $service The email service to send emails
     */
    public function __construct(
        private EmailService $service,

        #[Inject('option.site.email_verification_policy')]
        private ?string $policy = null,
    ) {}

    /**
     * Invoked when a UserCreatedEvent or EmailVerifiedEvent is dispatched.
     *
     * This listener responds to the event by sending a welcome email to the user.
     *
     * @param UserCreatedEvent|EmailVerifiedEvent $event The user creation or 
     * email verification event
     */
    public function __invoke(UserCreatedEvent|EmailVerifiedEvent $event)
    {
        $user = $event->user;

        if (
            $this->policy == 'strict'
            && !$user->isEmailVerified()->value
        ) {
            // Skip sending welcome email if email verification is required
            // Welcome email will be sent after email verification
            return;
        }

        if (
            $this->policy == 'relaxed'
            && $user->isEmailVerified()->value
        ) {
            // Welcome email has already been sent
            return;
        }

        $data = [
            'user' => new UserResource($user),
            'locale' => $user->getLanguage()->value,
        ];

        $this->service->sendTemplate(
            $user->getEmail()->value,
            '@emails/welcome.twig',
            $data
        );
    }
}
