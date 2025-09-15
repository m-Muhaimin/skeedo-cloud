<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Api\Billing;

use Easy\Router\Attributes\Middleware;
use Easy\Router\Attributes\Path;
use Presentation\Middlewares\DemoEnvironmentMiddleware;
use Presentation\RequestHandlers\Api\Api;

#[Middleware(DemoEnvironmentMiddleware::class)]
#[Path('/billing')]
abstract class BillingApi extends Api
{
}
