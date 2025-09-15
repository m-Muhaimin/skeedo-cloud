<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\App\Billing;

use Billing\Application\Commands\ReadOrderCommand;
use Billing\Domain\Entities\OrderEntity;
use Billing\Domain\Exceptions\OrderNotFoundException;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Presentation\Resources\Api\OrderResource;
use Presentation\Response\RedirectResponse;
use Presentation\Response\ViewResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use User\Domain\Entities\UserEntity;

#[Route(path: '/orders/[uuid:id]/receipt', method: RequestMethod::GET)]
class ReceiptViewRequestHandler extends BillingView implements
    RequestHandlerInterface
{
    public function __construct(
        private Dispatcher $dispatcher,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var UserEntity */
        $user = $request->getAttribute(UserEntity::class);

        $id = $request->getAttribute('id');

        try {
            $cmd = new ReadOrderCommand($id);

            /** @var OrderEntity */
            $order = $this->dispatcher->dispatch($cmd);
        } catch (OrderNotFoundException $th) {
            return new RedirectResponse('/app/billing');
        }

        if (
            (string)$order->getWorkspace()->getId()->getValue()
            != (string) $user->getCurrentWorkspace()->getId()->getValue()
        ) {
            return new RedirectResponse('/app/billing');
        }

        return new ViewResponse(
            '/templates/app/billing/receipt.twig',
            [
                'order' => new OrderResource($order),
            ]
        );
    }
}
