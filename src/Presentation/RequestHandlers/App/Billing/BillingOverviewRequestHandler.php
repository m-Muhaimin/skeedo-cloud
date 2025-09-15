<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\App\Billing;

use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Presentation\Response\ViewResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[Route(path: '/[overview]?', method: RequestMethod::GET)]
class BillingOverviewRequestHandler extends BillingView implements
    RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new ViewResponse(
            '/templates/app/billing/overview.twig'
        );
    }
}
