<?php

declare(strict_types=1);

namespace Presentation\Middlewares;

use Presentation\Exceptions\UnauthorizedException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use User\Domain\Entities\UserEntity;
use User\Domain\ValueObjects\Role;

class DemoEnvironmentMiddleware implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        /** @var ?UserEntity */
        $user = $request->getAttribute(UserEntity::class);

        if (
            env('DEMO_SUPERADMIN_EMAIL')
            && $user && $user->getRole() == Role::ADMIN
        ) {
            $allowedEmails = array_filter(
                array_map('trim', explode(',', env('DEMO_SUPERADMIN_EMAIL', '')))
            );

            if (in_array($user->getEmail()->value, $allowedEmails)) {
                if (env('ENVIRONMENT') == 'demo') {
                    // Override environment to production
                    $_ENV['ENVIRONMENT'] = 'prod';
                }

                return $handler->handle($request);
            }
        }

        if (
            env('ENVIRONMENT') == 'demo'
            && $request->getMethod() != 'GET'
        ) {
            throw new UnauthorizedException('This feature is disabled in demo environment.');
        }

        return $handler->handle($request);
    }
}
