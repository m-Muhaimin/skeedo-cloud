<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin;

use Billing\Application\Commands\ReadSubscriptionCommand;
use Billing\Domain\Entities\SubscriptionEntity;
use Billing\Domain\Exceptions\SubscriptionNotFoundException;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Presentation\Resources\Admin\Api\SubscriptionResource;
use Presentation\Response\RedirectResponse;
use Presentation\Response\ViewResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;

#[Route(path: '/subscriptions/[uuid:id]', method: RequestMethod::GET)]
class SubscriptionRequestHandler extends AbstractAdminViewRequestHandler implements
    RequestHandlerInterface
{
    public function __construct(
        private Dispatcher $dispatcher
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $id = $request->getAttribute('id');

        $cmd = ReadSubscriptionCommand::createById($id);

        try {
            /** @var SubscriptionEntity */
            $sub = $this->dispatcher->dispatch($cmd);
        } catch (SubscriptionNotFoundException) {
            return new RedirectResponse('/admin/subscriptions');
        }

        return new ViewResponse(
            '/templates/admin/subscription.twig',
            ['subscription' => new SubscriptionResource($sub, ['order', 'workspace', 'workspace.user'])]
        );
    }
}
