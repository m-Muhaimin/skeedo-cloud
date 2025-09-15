<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin\Api\Subscriptions;

use Billing\Application\Commands\CancelSubscriptionCommand;
use Billing\Domain\Entities\SubscriptionEntity;
use Billing\Domain\Exceptions\SubscriptionNotFoundException;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Presentation\Exceptions\NotFoundException;
use Presentation\Resources\Admin\Api\SubscriptionResource;
use Presentation\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;

#[Route(path: '/[uuid:id]', method: RequestMethod::DELETE)]
class CancelSubscriptionRequestHandler extends SubscriptionApi implements
    RequestHandlerInterface
{
    public function __construct(
        private Dispatcher $dispatcher
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $cmd = new CancelSubscriptionCommand($request->getAttribute('id'));

        try {
            /** @var SubscriptionEntity */
            $sub = $this->dispatcher->dispatch($cmd);
        } catch (SubscriptionNotFoundException $th) {
            throw new NotFoundException(
                param: 'id',
                previous: $th
            );
        }

        return new JsonResponse(
            new SubscriptionResource($sub, ['order', 'workspace', 'workspace.user'])
        );
    }
}
