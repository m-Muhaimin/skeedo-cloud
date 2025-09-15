<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin\Api\Reports;

use Easy\Router\Attributes\Path;
use Presentation\RequestHandlers\Admin\Api\AdminApi;

#[Path('/reports')]
abstract class ReportsApi extends AdminApi
{
}
