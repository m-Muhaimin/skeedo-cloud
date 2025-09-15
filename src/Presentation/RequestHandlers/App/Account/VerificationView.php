<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\App\Account;

use Easy\Container\Attributes\Inject;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Presentation\Response\RedirectResponse;
use Presentation\Response\ViewResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use User\Domain\Entities\UserEntity;

#[Route(path: '/verification', method: RequestMethod::GET)]
class VerificationView extends AccountView implements
    RequestHandlerInterface
{
    public function __construct(
        #[Inject('option.site.email_verification_policy')]
        private ?string $policy = null
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {

        /** @var UserEntity $user */
        $user = $request->getAttribute(UserEntity::class);

        if (
            $user->isEmailVerified()->value
            || !in_array($this->policy, ['strict', 'relaxed'])
        ) {
            return new RedirectResponse('/app/account');
        }

        return new ViewResponse(
            '/templates/app/account/verification.twig',
        );
    }
}
