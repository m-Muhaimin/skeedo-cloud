<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin\Api\Orders;

use Billing\Application\Commands\CancelOrderCommand;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Billing\Application\Commands\FulfillOrderCommand;
use Billing\Application\Commands\ReadOrderCommand;
use Billing\Domain\Exceptions\InvalidOrderStateException;
use Billing\Domain\Exceptions\OrderNotFoundException;
use Easy\Http\Message\StatusCode;
use Presentation\Exceptions\HttpException;
use Presentation\Exceptions\NotFoundException;
use Presentation\Resources\Admin\Api\OrderResource;
use Presentation\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;

#[Route(path: '/[uuid:id]/[reject|approve:action]', method: RequestMethod::DELETE)]
class OrderStatusApi extends OrdersApi implements
    RequestHandlerInterface
{
    public function __construct(
        private Dispatcher $dispatcher
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $id = $request->getAttribute('id');
        $action = $request->getAttribute('action');

        match ($action) {
            'reject' => $cmd = new CancelOrderCommand($id),
            'approve' => $cmd = new FulfillOrderCommand($id),
        };

        try {
            $this->dispatcher->dispatch($cmd);
        } catch (OrderNotFoundException $th) {
            throw new NotFoundException(
                param: 'id',
                previous: $th
            );
        } catch (InvalidOrderStateException $th) {
            throw new HttpException(
                message: 'Invalid order state',
                statusCode: StatusCode::BAD_REQUEST,
                previous: $th
            );
        }

        $cmd = new ReadOrderCommand($id);
        $order = $this->dispatcher->dispatch($cmd);

        return new JsonResponse(new OrderResource($order, ['subscription', 'workspace', 'workspace.user']));
    }
}
