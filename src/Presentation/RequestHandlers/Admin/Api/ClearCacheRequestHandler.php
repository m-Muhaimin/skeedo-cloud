<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin\Api;

use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Presentation\Response\EmptyResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Shared\Infrastructure\CacheManager;

#[Route(path: '/cache', method: RequestMethod::DELETE)]
class ClearCacheRequestHandler extends AdminApi implements
    RequestHandlerInterface
{
    public function __construct(
        private CacheManager $cacheManager,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->cacheManager->clearCache();
        return new EmptyResponse();
    }
}
