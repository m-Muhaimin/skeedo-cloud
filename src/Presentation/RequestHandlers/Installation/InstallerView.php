<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Installation;

use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Middleware;
use Easy\Router\Attributes\Route;
use Presentation\Middlewares\ViewMiddleware;
use Presentation\Response\ViewResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[Middleware(ViewMiddleware::class)]
#[Route(path: '/', method: RequestMethod::GET)]
class InstallerView extends AbstractRequestHandler implements
    RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $ip = $request->getServerParams()['SERVER_ADDR'] ?? null;

        return new ViewResponse(
            'templates/install/index.twig',
            [
                'ip' => $ip,
            ]
        );
    }
}
