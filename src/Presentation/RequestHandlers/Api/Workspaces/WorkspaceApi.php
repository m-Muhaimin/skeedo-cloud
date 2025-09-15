<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Api\Workspaces;

use Easy\Router\Attributes\Middleware;
use Easy\Router\Attributes\Path;
use Presentation\Middlewares\DemoEnvironmentMiddleware;
use Presentation\RequestHandlers\Api\Api;

#[Middleware(DemoEnvironmentMiddleware::class)]
#[Path('/workspaces')]
abstract class WorkspaceApi extends Api
{
}
