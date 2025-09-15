<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin\Api;

use Easy\Router\Attributes\Middleware;
use Easy\Router\Attributes\Path;
use Presentation\Middlewares\DemoEnvironmentMiddleware;
use Presentation\RequestHandlers\Admin\AbstractAdminRequestHandler;

#[Middleware(DemoEnvironmentMiddleware::class)]
#[Path('/api')]
abstract class AdminApi extends AbstractAdminRequestHandler
{
}
