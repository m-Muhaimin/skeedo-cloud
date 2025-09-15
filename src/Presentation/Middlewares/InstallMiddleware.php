<?php

declare(strict_types=1);

namespace Presentation\Middlewares;

use Override;
use Presentation\Response\RedirectResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class InstallMiddleware implements MiddlewareInterface
{
    #[Override]
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $env = env('ENVIRONMENT', 'install');

        $path = $request->getUri()->getPath();

        if (strpos($path, '/install') === false) {

            if ($env == 'install') {
                return new RedirectResponse('/install');
            }

            return $handler->handle($request);
        }

        if ($env != 'install') {
            return new RedirectResponse('/');
        }

        return $handler->handle($request);
    }
}
