<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin\Api\Payouts;

use Easy\Router\Attributes\Path;
use Presentation\RequestHandlers\Admin\Api\AdminApi;

#[Path('/payouts')]
abstract class PayoutsApi extends AdminApi {}
