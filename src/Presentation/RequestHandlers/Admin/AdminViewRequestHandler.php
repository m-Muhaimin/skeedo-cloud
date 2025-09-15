<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin;

use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Presentation\Response\ViewResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[Route(
    path: '/[analytics|categories|presets|assistants|voices|users|workspaces|plans|subscriptions|orders|coupons|templates|plugins|themes:view]',
    method: RequestMethod::GET
)]
class AdminViewRequestHandler extends AbstractAdminViewRequestHandler implements
    RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $view = $request->getAttribute('view');

        $template = $view;
        if ($view == 'templates') {
            $template = 'presets';
        }

        return new ViewResponse(
            '/templates/admin/' . $template . '.twig'
        );
    }
}
