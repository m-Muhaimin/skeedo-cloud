<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin\Api\Plugins;

use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Plugin\Application\Commands\CountPluginsCommand;
use Plugin\Domain\ValueObjects\Type;
use Presentation\Resources\CountResource;
use Presentation\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;

#[Route(path: '/count', method: RequestMethod::GET)]
class CountPluginsRequestHandler extends PluginsApi
implements RequestHandlerInterface
{

    public function __construct(
        private Dispatcher $dispatcher
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $cmd = new CountPluginsCommand();
        $params = (object) $request->getQueryParams();

        if (property_exists($params, 'type')) {
            $cmd->type = Type::from($params->type);
        }

        if (property_exists($params, 'status')) {
            $cmd->setStatus($params->status);
        }

        if (property_exists($params, 'query')) {
            $cmd->query = $params->query;
        }

        $count = $this->dispatcher->dispatch($cmd);
        return new JsonResponse(new CountResource($count));
    }
}
