<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\App\Billing;

use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Presentation\Response\RedirectResponse;
use Presentation\Response\ViewResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use User\Domain\Entities\UserEntity;

#[Route(path: '/packs', method: RequestMethod::GET)]
class ListPacksRequestHandler extends BillingView implements
    RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var UserEntity */
        $user = $request->getAttribute(UserEntity::class);

        $ws = $user->getCurrentWorkspace();

        if (!$ws->getSubscription()) {
            return new RedirectResponse('/app/billing');
        }

        return new ViewResponse(
            '/templates/app/billing/packs.twig'
        );
    }
}
