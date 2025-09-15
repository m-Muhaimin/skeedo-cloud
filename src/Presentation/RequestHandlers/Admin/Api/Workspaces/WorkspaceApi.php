<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin\Api\Workspaces;

use Easy\Router\Attributes\Path;
use Presentation\RequestHandlers\Admin\Api\AdminApi;

#[Path('/workspaces')]
abstract class WorkspaceApi extends AdminApi
{
}
