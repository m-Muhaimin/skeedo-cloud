<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers;

use Easy\Container\Attributes\Inject;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Middleware;
use Easy\Router\Attributes\Route;
use Presentation\Cookies\ReferralCookie;
use Presentation\Cookies\UserCookie;
use Presentation\Jwt\UserJwt;
use Presentation\Middlewares\ViewMiddleware;
use Presentation\Response\RedirectResponse;
use Presentation\Response\ViewResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use User\Domain\Entities\UserEntity;
use User\Domain\ValueObjects\Role;
use User\Domain\ValueObjects\Status;
use User\Infrastructure\SSO\Exceptions\IdentityProviderNotFoundException;
use User\Infrastructure\SSO\Exceptions\InvalidCodeException;
use User\Infrastructure\SSO\IdentityProviderFactoryInterface;

#[Middleware(ViewMiddleware::class)]
#[Route(path: '/auth/[*:provider]', method: RequestMethod::GET)]
class AuthCallbackRequestHandler extends AbstractRequestHandler implements
    RequestHandlerInterface
{
    public function __construct(
        private IdentityProviderFactoryInterface $factory,

        #[Inject('option.site.user_accounts_enabled')]
        private bool $userAccountsEnabled = true,
    ) {}

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $user = $request->getAttribute(UserEntity::class);
        if ($user) {
            return new RedirectResponse(
                $user->getRole() == Role::ADMIN ? '/admin' : '/app'
            );
        }

        if (!isset($request->getQueryParams()['code'])) {
            return new RedirectResponse('/login');
        }

        try {
            $provider = $this->factory->getIdentityProvider(
                $request->getAttribute('provider')
            );
        } catch (IdentityProviderNotFoundException $th) {
            return new RedirectResponse('/login');
        }

        try {
            $ipinfo = $request->getCookieParams()['ipinfo'] ?? '{}';
            $params = json_decode($ipinfo, true);

            $params = array_filter(
                $params,
                fn($value, $key) =>
                is_string($value)
                    && in_array($key, ['ip', 'country_code', 'city_name']),
                ARRAY_FILTER_USE_BOTH
            );

            $ref = $request->getCookieParams()['ref'] ?? null;
            if ($ref) {
                $params['ref'] = $ref;
            }

            $user = $provider->getUser(
                $request->getQueryParams()['code'],
                $params
            );
        } catch (InvalidCodeException $th) {
            return new RedirectResponse('/login');
        }

        if ($user->getStatus() !== Status::ACTIVE) {
            return new RedirectResponse('/login');
        }

        // If user accounts are disabled, only admins can login
        // This will still create a new user account.
        if (!$this->userAccountsEnabled && $user->getRole() !== Role::ADMIN) {
            return new RedirectResponse('/login');
        }

        $jwt = new UserJwt($user);
        $tokenString = $jwt->getJwtString();
        $cookie = new UserCookie($tokenString);

        $refCookie = new ReferralCookie('');

        return (new ViewResponse(
            '/templates/auth/callback.twig',
            ['jwt' => $tokenString]
        ))
            ->withAddedHeader('Set-Cookie', $cookie->toHeaderValue())
            ->withAddedHeader('Set-Cookie', $refCookie->toHeaderValue(true));
    }
}
