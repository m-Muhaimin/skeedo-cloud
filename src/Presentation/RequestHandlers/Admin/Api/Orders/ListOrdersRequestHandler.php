<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin\Api\Orders;

use Billing\Application\Commands\ListOrdersCommand;
use Billing\Domain\Entities\OrderEntity;
use Billing\Domain\Exceptions\OrderNotFoundException;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Iterator;
use Presentation\Resources\Admin\Api\OrderResource;
use Presentation\Resources\ListResource;
use Presentation\Response\JsonResponse;
use Presentation\Validation\ValidationException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;

#[Route(path: '/', method: RequestMethod::GET)]
class ListOrdersRequestHandler extends OrdersApi implements
    RequestHandlerInterface
{
    public function __construct(
        private Dispatcher $dispatcher
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $cmd = new ListOrdersCommand();
        $params = (object) $request->getQueryParams();

        if (property_exists($params, 'status')) {
            $cmd->setStatus($params->status);
        }

        if (property_exists($params, 'workspace')) {
            $cmd->setWorkspace($params->workspace);
        }

        if (property_exists($params, 'plan')) {
            $cmd->setPlan($params->plan);
        }

        if (property_exists($params, 'coupon')) {
            $cmd->setCoupon($params->coupon);
        }

        if (property_exists($params, 'plan_snapshot')) {
            $cmd->setPlanSnapshot($params->plan_snapshot);
        }

        if (property_exists($params, 'billing_cycle')) {
            $cmd->setBillingCycle($params->billing_cycle);
        }

        if (property_exists($params, 'query') && $params->query) {
            $cmd->query = $params->query;
        }

        if (property_exists($params, 'sort') && $params->sort) {
            $sort = explode(':', $params->sort);
            $orderBy = $sort[0];
            $dir = $sort[1] ?? 'asc';
            $cmd->setOrderBy($orderBy, $dir);
        }

        if (property_exists($params, 'starting_after') && $params->starting_after) {
            $cmd->setCursor(
                $params->starting_after,
                'starting_after'
            );
        } elseif (property_exists($params, 'ending_before') && $params->ending_before) {
            $cmd->setCursor(
                $params->ending_before,
                'ending_before'
            );
        }

        if (property_exists($params, 'limit')) {
            $cmd->setLimit((int) $params->limit);
        }

        try {
            /** @var Iterator<int,OrderEntity> $orders */
            $orders = $this->dispatcher->dispatch($cmd);
        } catch (OrderNotFoundException $th) {
            throw new ValidationException(
                'Invalid cursor',
                property_exists($params, 'starting_after')
                    ? 'starting_after'
                    : 'ending_before',
                previous: $th
            );
        }

        $res = new ListResource();
        foreach ($orders as $order) {
            $res->pushData(new OrderResource($order, ['workspace', 'workspace.user']));
        }

        return new JsonResponse($res);
    }
}
