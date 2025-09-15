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
    path: '/affiliates/[payouts:view]?',
    method: RequestMethod::GET
)]
class AffiliatesView extends AbstractAdminViewRequestHandler implements
    RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $view = $request->getAttribute('view');

        if (!$view) {
            $view = 'accounts';
        }

        return new ViewResponse(
            '/templates/admin/affiliates/' . $view . '.twig'
        );
    }
}
