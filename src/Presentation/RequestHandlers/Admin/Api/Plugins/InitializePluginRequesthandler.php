<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin\Api\Plugins;

use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Plugin\Application\Commands\InitializePluginCommand;
use Plugin\Domain\Exceptions\PluginNotFoundException;
use Plugin\Domain\PluginWrapper;
use Presentation\Exceptions\NotFoundException;
use Presentation\Resources\Admin\Api\PluginResource;
use Presentation\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;

#[Route(path: '/[*:vendor]/[.:package]/initialize', method: RequestMethod::PUT)]
#[Route(path: '/[*:vendor]/[*:package]/initialize', method: RequestMethod::POST)]
class InitializePluginRequesthandler extends PluginsApi implements
    RequestHandlerInterface
{
    public function __construct(
        public Dispatcher $dispatcher
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $vendor = $request->getAttribute('vendor');
        $package = $request->getAttribute('package');

        $cmd = new InitializePluginCommand($vendor . "/" . $package);

        try {
            /** @var PluginWrapper */
            $pw = $this->dispatcher->dispatch($cmd);
        } catch (PluginNotFoundException $th) {
            throw new NotFoundException(
                previous: $th
            );
        }

        return new JsonResponse(new PluginResource($pw->context));
    }
}
