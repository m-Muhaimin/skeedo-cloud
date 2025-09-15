<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin\Api\Plugins;

use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Plugin\Application\Commands\UninstallPluginCommand;
use Plugin\Domain\Exceptions\PluginNotFoundException;
use Presentation\Exceptions\NotFoundException;
use Presentation\Response\EmptyResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;

#[Route(path: '/[:vendor]/[:package]', method: RequestMethod::DELETE)]
class DeletePluginRequesthandler extends PluginsApi implements
    RequestHandlerInterface
{
    public function __construct(
        private Dispatcher $dispatcher
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $vendor = $request->getAttribute('vendor');
        $package = $request->getAttribute('package');

        $cmd = new UninstallPluginCommand($vendor . "/" . $package);

        try {
            $this->dispatcher->dispatch($cmd);
        } catch (PluginNotFoundException $th) {
            throw new NotFoundException(
                previous: $th
            );
        }

        return new EmptyResponse();
    }
}
