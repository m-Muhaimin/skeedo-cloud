<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin\Api\PlanSnapshots;

use Easy\Router\Attributes\Path;
use Presentation\RequestHandlers\Admin\Api\AdminApi;

#[Path('/plan-snapshots')]
abstract class SnapshotApi extends AdminApi
{
}
