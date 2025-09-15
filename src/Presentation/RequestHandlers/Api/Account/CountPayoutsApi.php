<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Api\Account;

use Affiliate\Application\Commands\CountPayoutsCommand;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Presentation\Resources\CountResource;
use Presentation\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use User\Domain\Entities\UserEntity;

#[Route(path: '/count', method: RequestMethod::GET)]
class CountPayoutsApi extends PayoutsApi implements RequestHandlerInterface
{
    public function __construct(
        private Dispatcher $dispatcher
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var UserEntity */
        $user = $request->getAttribute(UserEntity::class);
        $params = (object) $request->getQueryParams();

        $cmd = new CountPayoutsCommand();
        $cmd->user = $user;

        if (property_exists($params, 'status')) {
            $cmd->setStatus($params->status);
        }

        /** @var int */
        $count = $this->dispatcher->dispatch($cmd);
        return new JsonResponse(new CountResource($count));
    }
}
