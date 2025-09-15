<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers;

use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Middleware;
use Easy\Router\Attributes\Route;
use Presentation\Cookies\PreviewCookie;
use Presentation\Middlewares\ViewMiddleware;
use Presentation\Response\RedirectResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[Middleware(ViewMiddleware::class)]
#[Route(path: '/preview', method: RequestMethod::GET)]
class PreviewRequestHandler extends AbstractRequestHandler implements
    RequestHandlerInterface
{
    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(
        ServerRequestInterface $request
    ): ResponseInterface {
        $resp = new RedirectResponse('/');

        $theme = $request->getQueryParams()['theme'] ?? null;

        if ($theme) {
            $cookie = new PreviewCookie($theme);
            $resp = $resp->withHeader('Set-Cookie', $cookie->toHeaderValue());
        }

        return $resp;
    }
}
