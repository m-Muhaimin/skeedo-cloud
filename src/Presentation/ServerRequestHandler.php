<?php

declare(strict_types=1);

namespace Presentation;

use Easy\Http\Server\DispatcherInterface;
use Easy\Http\Server\Exceptions\DispatcherExceptionInterface;
use Easy\Http\Server\Exceptions\MethodNotAllowedExceptionInterface;
use Easy\Http\Server\Exceptions\RouteNotFoundExceptionInterface;
use Easy\Http\Server\RouteInterface;
use Easy\HttpServerHandler\HttpServerHandler;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ServerRequestHandler implements RequestHandlerInterface
{
    public function __construct(
        private DispatcherInterface $dispatcher
    ) {
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws DispatcherExceptionInterface
     * @throws MethodNotAllowedExceptionInterface
     * @throws RouteNotFoundExceptionInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $route = $this->dispatcher->dispatch($request);

        foreach ($route->getParams() as $param) {
            $request = $request->withAttribute(
                $param->getKey(),
                $param->getValue()
            );
        }

        $request = $request
            ->withAttribute(RouteInterface::class, $route)
            ->withAttribute(
                RequestHandlerInterface::class,
                $route->getRequestHandler()
            );

        $handler = new HttpServerHandler(
            $route->getRequestHandler(),
            ...$route->getMiddlewares()
        );

        return $handler->handle($request);
    }
}
