<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin\Api\Affiliates;

use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Shared\Infrastructure\CommandBus\Exception\NoHandlerFoundException;
use Presentation\Response\JsonResponse;
use Presentation\Resources\CountResource;
use User\Application\Commands\CountUsersCommand;

#[Route(path: '/count', method: RequestMethod::GET)]
class CountAffiliatesRequestHandler extends AffiliateApi implements
    RequestHandlerInterface
{
    /**
     * @param Dispatcher $dispatcher
     * @return void
     */
    public function __construct(
        private Dispatcher $dispatcher
    ) {}

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws NoHandlerFoundException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $cmd = new CountUsersCommand();
        $params = (object) $request->getQueryParams();

        if (property_exists($params, 'query') && $params->query) {
            $cmd->query = $params->query;
        }

        $count = $this->dispatcher->dispatch($cmd);
        return new JsonResponse(new CountResource($count));
    }
}
