<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Api\Billing;

use Billing\Application\Commands\CountOrdersCommand;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Presentation\Resources\CountResource;
use Presentation\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Workspace\Domain\Entities\WorkspaceEntity;

#[Route(path: '/orders/count', method: RequestMethod::GET)]
class CountOrdersRequestHandler extends BillingApi implements
    RequestHandlerInterface
{
    public function __construct(
        private Dispatcher $dispatcher
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var WorkspaceEntity */
        $ws = $request->getAttribute(WorkspaceEntity::class);

        $params = (object) $request->getQueryParams();

        $cmd = new CountOrdersCommand();
        $cmd->setWorkspace($ws);

        if (property_exists($params, 'status')) {
            $cmd->setStatus($params->status);
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

        $count = $this->dispatcher->dispatch($cmd);
        return new JsonResponse(new CountResource($count));
    }
}
