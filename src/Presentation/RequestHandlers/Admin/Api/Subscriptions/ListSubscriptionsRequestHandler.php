<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin\Api\Subscriptions;

use Billing\Application\Commands\ListSubscriptionsCommand;
use Billing\Domain\Entities\SubscriptionEntity;
use Billing\Domain\Exceptions\SubscriptionNotFoundException;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Iterator;
use Presentation\Resources\Admin\Api\SubscriptionResource;
use Presentation\Resources\ListResource;
use Presentation\Response\JsonResponse;
use Presentation\Validation\ValidationException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;

#[Route(path: '/', method: RequestMethod::GET)]
class ListSubscriptionsRequestHandler extends SubscriptionApi implements
    RequestHandlerInterface
{
    public function __construct(
        private Dispatcher $dispatcher
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $cmd = new ListSubscriptionsCommand();
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
            /** @var Iterator<int,SubscriptionEntity> $subscriptions */
            $subscriptions = $this->dispatcher->dispatch($cmd);
        } catch (SubscriptionNotFoundException $th) {
            throw new ValidationException(
                'Invalid cursor',
                property_exists($params, 'starting_after')
                    ? 'starting_after'
                    : 'ending_before',
                previous: $th
            );
        }

        $res = new ListResource();
        foreach ($subscriptions as $sub) {
            $res->pushData(new SubscriptionResource($sub, ['workspace', 'order']));
        }

        return new JsonResponse($res);
    }
}
