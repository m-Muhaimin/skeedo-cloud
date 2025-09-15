<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin\Api\Subscriptions;

use Easy\Router\Attributes\Path;
use Presentation\RequestHandlers\Admin\Api\AdminApi;

#[Path('/subscriptions')]
abstract class SubscriptionApi extends AdminApi
{
}
