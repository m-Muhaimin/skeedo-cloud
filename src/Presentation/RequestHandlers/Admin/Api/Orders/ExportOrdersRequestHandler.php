<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin\Api\Orders;

use Billing\Application\Commands\ListOrdersCommand;
use Billing\Domain\Entities\OrderEntity;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Presentation\Resources\Admin\Api\OrderResource;
use Presentation\Response\EmptyResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Shared\Infrastructure\CommandBus\Exception\NoHandlerFoundException;
use Shared\Infrastructure\ExportService;
use Symfony\Component\Mime\Exception\InvalidArgumentException;
use Symfony\Component\Mime\Exception\LogicException;
use Traversable;
use User\Domain\Entities\UserEntity;

#[Route(path: '/export', method: RequestMethod::POST)]
class ExportOrdersRequestHandler extends OrdersApi implements
    RequestHandlerInterface
{
    public function __construct(
        private Dispatcher $dispatcher,
        private ExportService $service
    ) {
    }

    /**
     * @throws NoHandlerFoundException
     * @throws InvalidArgumentException
     * @throws LogicException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var UserEntity */
        $user = $request->getAttribute(UserEntity::class);

        $this->service->exportToEmail(
            $user->getEmail(),
            $this->getOrders($request)
        );

        return new EmptyResponse();
    }

    /**
     * @return Traversable<OrderResource>
     * @throws NoHandlerFoundException
     */
    private function getOrders(ServerRequestInterface $request): Traversable
    {
        $params = (object) $request->getQueryParams();

        $cmd = new ListOrdersCommand();
        $cmd->sortDirection = null; // no sorting by default
        $cmd->maxResults = null; // no limit

        if (property_exists($params, 'status')) {
            $cmd->setStatus($params->status);
        }

        if (property_exists($params, 'workspace')) {
            $cmd->setWorkspace($params->workspace);
        }

        if (property_exists($params, 'plan')) {
            $cmd->setPlan($params->plan);
        }

        if (property_exists($params, 'plan_snapshot')) {
            $cmd->setPlanSnapshot($params->plan_snapshot);
        }

        if (property_exists($params, 'billing_cycle')) {
            $cmd->setBillingCycle($params->billing_cycle);
        }

        if (property_exists($params, 'sort') && $params->sort) {
            $sort = explode(':', $params->sort);
            $orderBy = $sort[0];
            $dir = $sort[1] ?? 'desc';
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

        /** @var Traversable<int,OrderEntity> $orders */
        $orders = $this->dispatcher->dispatch($cmd);

        foreach ($orders as $order) {
            yield new OrderResource($order, ['workspace', 'workspace.user']);
        }
    }
}
