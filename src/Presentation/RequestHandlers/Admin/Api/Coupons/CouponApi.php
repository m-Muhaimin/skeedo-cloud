<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Admin\Api\Coupons;

use Easy\Router\Attributes\Path;
use Presentation\RequestHandlers\Admin\Api\AdminApi;

#[Path('/coupons')]
abstract class CouponApi extends AdminApi {}
