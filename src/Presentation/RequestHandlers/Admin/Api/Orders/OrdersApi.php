<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin\Api\Orders;

use Easy\Router\Attributes\Path;
use Presentation\RequestHandlers\Admin\Api\AdminApi;

#[Path('/orders')]
abstract class OrdersApi extends AdminApi
{
}
