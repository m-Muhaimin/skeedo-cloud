<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin\Api\Plugins;

use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Iterator;
use Plugin\Application\Commands\ListPluginsCommand;
use Plugin\Domain\PluginWrapper;
use Plugin\Domain\ValueObjects\Type;
use Presentation\Resources\Admin\Api\PluginResource;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Presentation\Response\JsonResponse;
use Presentation\Resources\ListResource;

#[Route(path: '/', method: RequestMethod::GET)]
class ListPluginsRequestHandler extends PluginsApi implements
    RequestHandlerInterface
{
    public function __construct(
        private Dispatcher $dispatcher
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $cmd = new ListPluginsCommand();
        $params = (object) $request->getQueryParams();

        if (property_exists($params, 'type')) {
            $cmd->type = Type::from($params->type);
        }

        if (property_exists($params, 'status')) {
            $cmd->setStatus($params->status);
        }

        if (property_exists($params, 'query') && $params->query) {
            $cmd->query = $params->query;
        }

        /** @var Iterator<int,PluginWrapper> $plugins */
        $plugins = $this->dispatcher->dispatch($cmd);

        $res = new ListResource();

        foreach ($plugins as $pw) {
            $res->pushData(new PluginResource($pw->context));
        }

        return new JsonResponse($res);
    }
}
