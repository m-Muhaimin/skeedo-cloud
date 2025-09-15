<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin\Api\Subscriptions;

use Billing\Application\Commands\CountSubscriptionsCommand;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Presentation\Resources\CountResource;
use Presentation\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Shared\Infrastructure\CommandBus\Exception\NoHandlerFoundException;

#[Route(path: '/count', method: RequestMethod::GET)]
class CountSubscriptionsRequestHandler extends SubscriptionApi implements
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
        $params = (object) $request->getQueryParams();

        $cmd = new CountSubscriptionsCommand();

        if (property_exists($params, 'status')) {
            $cmd->setStatus($params->status);
        }

        if (property_exists($params, 'workspace')) {
            $cmd->setWorkspace($params->workspace);
        }

        if (property_exists($params, 'plan')) {
            $cmd->setPlan($params->plan);
        }

        $count = $this->dispatcher->dispatch($cmd);
        return new JsonResponse(new CountResource($count));
    }
}
