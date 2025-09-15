<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin\Api\Options;

use Shared\Infrastructure\Services\ModelRegistry;
use Easy\Container\Attributes\Inject;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Presentation\Response\EmptyResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CommandBus\Dispatcher;

#[Route(path: '/llms/[uuid:id]', method: RequestMethod::DELETE)]
class DeleteLlmServerApi extends OptionsApi
implements RequestHandlerInterface
{
    public function __construct(
        private Dispatcher $dispatcher,
        private ModelRegistry $registry,

        #[Inject('config.dirs.root')]
        private string $root,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $id = $request->getAttribute('id');

        $registry = $this->registry;
        $registry['directory'] = array_filter($registry['directory'], function ($service) use ($id) {
            return $service['key'] !== $id;
        });

        $this->registry->save();

        return new EmptyResponse();
    }
}
