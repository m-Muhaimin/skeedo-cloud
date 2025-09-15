<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin;

use Billing\Application\Commands\ReadOrderCommand;
use Billing\Domain\Entities\OrderEntity;
use Billing\Domain\Exceptions\SubscriptionNotFoundException;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Presentation\Resources\Admin\Api\OrderResource;
use Presentation\Response\RedirectResponse;
use Presentation\Response\ViewResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Shared\Infrastructure\CommandBus\Exception\NoHandlerFoundException;

#[Route(path: '/orders/[uuid:id]', method: RequestMethod::GET)]
class OrderRequestHandler extends AbstractAdminViewRequestHandler implements
    RequestHandlerInterface
{
    public function __construct(
        private Dispatcher $dispatcher
    ) {
    }

    /**
     * @throws NoHandlerFoundException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $id = $request->getAttribute('id');

        $cmd = new ReadOrderCommand($id);

        try {
            /** @var OrderEntity */
            $order = $this->dispatcher->dispatch($cmd);
        } catch (SubscriptionNotFoundException) {
            return new RedirectResponse('/admin/orders');
        }

        return new ViewResponse(
            '/templates/admin/order.twig',
            ['order' => new OrderResource($order, ['subscription', 'workspace', 'workspace.user'])]
        );
    }
}
