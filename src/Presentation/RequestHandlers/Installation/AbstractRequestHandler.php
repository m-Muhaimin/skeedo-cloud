<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Installation;

use Easy\Router\Attributes\Middleware;
use Easy\Router\Attributes\Path;
use Presentation\Middlewares\ExceptionMiddleware;
use Presentation\Middlewares\InstallMiddleware;
use Presentation\Middlewares\LocaleMiddleware;
use Presentation\Middlewares\RequestBodyParserMiddleware;

#[Middleware(ExceptionMiddleware::class)]
#[Middleware(InstallMiddleware::class)]
#[Middleware(RequestBodyParserMiddleware::class)]
#[Middleware(LocaleMiddleware::class)]
#[Path('/install')]
abstract class AbstractRequestHandler
{
}
