<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Installation;

use Easy\Router\Attributes\Path;

#[Path('/api')]
abstract class InstallationApi extends AbstractRequestHandler
{
}
