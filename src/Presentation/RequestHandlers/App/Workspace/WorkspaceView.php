<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\App\Workspace;

use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Path;
use Easy\Router\Attributes\Route;
use Presentation\RequestHandlers\App\AppView;
use Presentation\Response\ViewResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[Path('/workspace')]
#[Route(path: '/', method: RequestMethod::GET)]
class WorkspaceView extends AppView implements
    RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new ViewResponse(
            '/templates/app/workspace/overview.twig',
        );
    }
}
