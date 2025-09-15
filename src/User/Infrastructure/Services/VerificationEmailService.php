<?php

declare(strict_types=1);

namespace User\Infrastructure\Services;

use Easy\Container\Attributes\Inject;
use Laminas\Diactoros\ServerRequestFactory;
use Presentation\Resources\Api\UserResource;
use Shared\Infrastructure\Email\EmailService;
use User\Domain\Entities\UserEntity;

class VerificationEmailService
{
    public function __construct(
        private EmailService $service,

        #[Inject('option.site.email_verification_policy')]
        private ?string $policy = null,
    ) {}

    public function send(UserEntity $user)
    {
        if (
            !in_array($this->policy, ['strict', 'relaxed'])
            || $user->isEmailVerified()->value
        ) {
            return;
        }

        // Create reset URL from the User ID and Recovery Token
        $path = '/verification/email'
            . '/' . (string) $user->getId()->getValue()
            . '/' . $user->getEmailVerificationToken()->value;

        $req = ServerRequestFactory::fromGlobals();
        $uri = $req->getUri()
            ->withPath($path)
            ->withQuery('')
            ->withFragment('');

        $data = [
            'user' => new UserResource($user),
            'verification_url' => (string) $uri,
            'locale' => $user->getLanguage()->value,
        ];

        $this->service->sendTemplate(
            $user->getEmail()->value,
            '@emails/verify-email.twig',
            $data
        );
    }
}
